<?php
/**
 * Plugin Name:       Blockwright Workbench
 * Description:       One-click developer tools for block-theme work: bulk-reset Site Editor overrides, rescan theme patterns, flush every cache, clear all transients. Off by default outside development. Pairs with Create Block Theme.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            Mark Russell
 * Author URI:        https://markrussell.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockwright-workbench
 *
 * @package Blockwright_Workbench
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BLOCKWRIGHT_WORKBENCH_VERSION' ) ) {
	define( 'BLOCKWRIGHT_WORKBENCH_VERSION', '1.0.0' );
}
if ( ! defined( 'BLOCKWRIGHT_WORKBENCH_DIR' ) ) {
	define( 'BLOCKWRIGHT_WORKBENCH_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'BLOCKWRIGHT_WORKBENCH_URL' ) ) {
	define( 'BLOCKWRIGHT_WORKBENCH_URL', plugin_dir_url( __FILE__ ) );
}

// The workbench gates itself (inc/workbench.php is_active()): it surfaces only in
// a 'local'/'development' environment or when wp-config defines
// define( 'BLOCKWRIGHT_WORKBENCH', true ). On production with neither, nothing
// loads and no button shows.
require_once BLOCKWRIGHT_WORKBENCH_DIR . 'inc/workbench.php';
