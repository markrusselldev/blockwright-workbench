<?php
/**
 * Blockwright workbench - developer workflow tools.
 *
 * Consolidates the recovered admin-bar dev tools (formerly split across several
 * files) into ONE
 * TOOL REGISTRY: a single admin-bar loop, a single dispatcher, and a single
 * notice handler. Each tool is one array entry (label, title, cap, destructive,
 * available, run) so adding a tool is a one-line change.
 *
 * GATING (consequential): Reset Site Editor is DESTRUCTIVE (it force-deletes
 * DB content). The workbench is OFF by default and only surfaces when it is active:
 *   - automatically in the 'development' environment (wp_get_environment_type), OR
 *   - anywhere wp-config defines  define( 'BLOCKWRIGHT_WORKBENCH', true );
 * On a normal production install with neither, nothing loads and no button shows.
 * Destructive tools also require a JS confirm().
 *
 * @package Blockwright_Workbench
 */

namespace Blockwright_Workbench;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DISPATCH_ACTION  = 'blockwright_workbench_run';
const NOTICE_TRANSIENT = 'blockwright_workbench_notice_';
const REQUIRED_CAP     = 'edit_theme_options';

/**
 * Is the workbench active for the current user/request?
 *
 * @return bool
 */
function is_active() {
	$is_dev_env = function_exists( 'wp_get_environment_type' )
		&& in_array( wp_get_environment_type(), array( 'local', 'development' ), true );

	$enabled = $is_dev_env || ( defined( 'BLOCKWRIGHT_WORKBENCH' ) && BLOCKWRIGHT_WORKBENCH );

	return $enabled && is_user_logged_in();
}

/**
 * The tool registry. One entry per tool; filterable so add-ons can extend it.
 *
 * Each entry: label (admin-bar text), title (tooltip), cap, destructive (bool),
 * available (callable|null returning bool), run (callable returning a message string).
 *
 * @return array<string, array<string, mixed>>
 */
function tools() {
	$ns    = __NAMESPACE__;
	$tools = array(
		'foc' => array(
			'label'       => __( 'Flush caches', 'blockwright-workbench' ),
			'title'       => __( 'Flush all caches (object cache, theme.json, OPcache).', 'blockwright-workbench' ),
			'destructive' => false,
			'run'         => $ns . '\\run_flush_caches',
		),
		'rse' => array(
			'label'       => __( 'Reset Site Editor', 'blockwright-workbench' ),
			'title'       => __( 'Reset Site Editor overrides (templates, parts, global styles) for this theme.', 'blockwright-workbench' ),
			'destructive' => true,
			'run'         => $ns . '\\run_reset_site_editor',
		),
		'rtp' => array(
			'label'       => __( 'Rescan patterns', 'blockwright-workbench' ),
			'title'       => __( 'Rescan theme patterns (unregister + core rescan).', 'blockwright-workbench' ),
			'destructive' => false,
			'run'         => $ns . '\\run_rescan_patterns',
		),
		'ct'  => array(
			'label'       => __( 'Clear transients', 'blockwright-workbench' ),
			'title'       => __( 'Clear all transients.', 'blockwright-workbench' ),
			'destructive' => false,
			'run'         => $ns . '\\run_clear_transients',
		),
	);

	/**
	 * Filter the workbench tool registry.
	 *
	 * @param array $tools The registered tools.
	 */
	return apply_filters( 'blockwright_workbench_tools', $tools );
}

/**
 * ---------------------------------------------------------------------------
 * UI: one admin-bar loop, one notice handler.
 * ---------------------------------------------------------------------------
 */

add_action(
	'admin_bar_menu',
	function ( \WP_Admin_Bar $bar ) {
		if ( ! is_active() || ! is_admin_bar_showing() || ! current_user_can( REQUIRED_CAP ) ) {
			return;
		}

		$bar->add_node(
			array(
				'id'    => 'blockwright-workbench',
				'title' => __( 'Workbench', 'blockwright-workbench' ),
			)
		);

		foreach ( tools() as $id => $tool ) {
			if ( isset( $tool['available'] ) && ! call_user_func( $tool['available'] ) ) {
				continue;
			}

			$url = wp_nonce_url(
				admin_url( 'admin-post.php?action=' . DISPATCH_ACTION . '&tool=' . rawurlencode( $id ) ),
				DISPATCH_ACTION . '_' . $id
			);

			$meta = array( 'title' => $tool['title'] );

			if ( ! empty( $tool['destructive'] ) ) {
				/* translators: %s: tool name, e.g. Reset Site Editor. */
				$confirm         = sprintf( __( 'Run %s? This deletes saved data and cannot be undone.', 'blockwright-workbench' ), $tool['label'] );
				$meta['onclick'] = 'return confirm(' . wp_json_encode( $confirm ) . ');';
				$meta['class']   = 'blockwright-workbench-destructive';
			}

			$bar->add_node(
				array(
					'id'     => 'blockwright-wb-' . $id,
					'parent' => 'blockwright-workbench',
					'title'  => $tool['label'],
					'href'   => $url,
					'meta'   => $meta,
				)
			);
		}
	},
	100
);

add_action(
	'admin_notices',
	function () {
		if ( ! is_active() ) {
			return;
		}

		$key = NOTICE_TRANSIENT . get_current_user_id();
		$msg = get_transient( $key );

		if ( false === $msg || '' === $msg ) {
			return;
		}

		delete_transient( $key );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( (string) $msg )
		);
	}
);

/**
 * ---------------------------------------------------------------------------
 * Dispatch: one handler for every tool.
 * ---------------------------------------------------------------------------
 */

add_action(
	'admin_post_' . DISPATCH_ACTION,
	function () {
		if ( ! is_active() ) {
			wp_die( esc_html__( 'The Blockwright workbench is not active.', 'blockwright-workbench' ) );
		}

		$tool_id = isset( $_GET['tool'] ) ? sanitize_key( wp_unslash( $_GET['tool'] ) ) : '';
		$tools   = tools();

		if ( '' === $tool_id || ! isset( $tools[ $tool_id ] ) ) {
			wp_die( esc_html__( 'Unknown workbench tool.', 'blockwright-workbench' ) );
		}

		check_admin_referer( DISPATCH_ACTION . '_' . $tool_id );

		if ( ! current_user_can( REQUIRED_CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do this.', 'blockwright-workbench' ) );
		}

		$tool    = $tools[ $tool_id ];
		$message = (string) call_user_func( $tool['run'] );

		set_transient( NOTICE_TRANSIENT . get_current_user_id(), $message, MINUTE_IN_SECONDS );

		$back = wp_get_referer();
		if ( ! $back || false !== strpos( $back, 'admin-post.php' ) ) {
			$back = admin_url();
		}

		wp_safe_redirect( $back );
		exit;
	}
);

/**
 * ---------------------------------------------------------------------------
 * Tool callbacks. Each returns a human-readable result message.
 * ---------------------------------------------------------------------------
 */

/**
 * Reset OPcache if available.
 *
 * @return bool
 */
function opcache_reset_maybe() {
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	return function_exists( 'opcache_reset' ) ? (bool) @opcache_reset() : false;
}

/**
 * FOC: flush object cache + theme.json cache + OPcache.
 *
 * @return string
 */
function run_flush_caches() {
	wp_cache_flush();

	if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
		wp_clean_theme_json_cache();
	}

	$opcache = opcache_reset_maybe();

	return $opcache
		? __( 'All caches flushed (object cache + theme.json + OPcache).', 'blockwright-workbench' )
		: __( 'All caches flushed (object cache + theme.json).', 'blockwright-workbench' );
}

/**
 * RSE: delete Site Editor DB overrides (templates, parts, global styles) for the
 * current theme so the theme's files win again. The file <-> database fix.
 *
 * @return string
 */
function run_reset_site_editor() {
	$theme_slug = get_stylesheet();
	$post_types = array( 'wp_template', 'wp_template_part', 'wp_global_styles' );
	$deleted    = 0;

	foreach ( $post_types as $post_type ) {
		$ids = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'any',
				'numberposts'            => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'slug',
						'terms'    => array( $theme_slug ),
					),
				),
			)
		);

		foreach ( $ids as $id ) {
			if ( wp_delete_post( $id, true ) ) {
				++$deleted;
			}
		}
	}

	if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
		wp_clean_theme_json_cache();
	}

	/* translators: %d: number of deleted overrides. */
	return sprintf( __( 'Reset Site Editor: deleted %d override(s) for the current theme.', 'blockwright-workbench' ), $deleted );
}

/**
 * RTP: drop the cached pattern file list so the next request re-reads the directory.
 *
 * WP caches each theme's pattern FILE LIST in a transient keyed by the theme's
 * cache_hash (WP_Theme::get_pattern_cache()). A new pattern FILE never appears until
 * that cache is cleared, which is the whole point of this tool.
 *
 * Clearing it is the entire job. Core registers theme patterns on EVERY request -
 * add_action( 'init', '_register_theme_block_patterns' ) in wp-includes/block-patterns.php,
 * and this tool redirects when it finishes, so the very next
 * request rebuilds the registry from a fresh directory read. Re-registering inside THIS
 * request would mean calling that core function directly, and core marks it
 * `@access private`; the redirect gets the same result through public API only.
 *
 * @return string
 */
function run_rescan_patterns() {
	$cleared = array();

	foreach ( array_unique( array( get_stylesheet(), get_template() ) ) as $theme_slug ) {
		$theme = wp_get_theme( $theme_slug );

		// WP_Theme::delete_pattern_cache() is public since 6.4; this plugin requires 7.0.
		if ( $theme->exists() ) {
			$theme->delete_pattern_cache();
			$cleared[] = $theme_slug;
		}
	}

	wp_cache_flush();

	if ( array() === $cleared ) {
		return __( 'No active theme found, so there was no pattern cache to clear.', 'blockwright-workbench' );
	}

	return __( 'Theme pattern cache cleared. Patterns are re-read from disk on the next page load.', 'blockwright-workbench' );
}

/**
 * CT: clear all transients.
 *
 * @return string
 */
function run_clear_transients() {
	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$count  = (int) $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" );
	$count += (int) $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_site\\_transient\\_%'" );
	// phpcs:enable

	wp_cache_flush();

	/* translators: %d: number of transient rows removed. */
	return sprintf( __( 'Cleared %d transient row(s).', 'blockwright-workbench' ), $count );
}
