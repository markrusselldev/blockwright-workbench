=== Blockwright Workbench ===
Contributors: markrussellwp
Tags: developer, block-theme, site-editor, reset, cache
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

One-click developer tools for block-theme work: bulk-reset Site Editor overrides, rescan theme patterns, flush every cache, clear all transients.

== Description ==

If you build block themes, you know the quiet frustration: you edit a template file, reload, and nothing changes, because a Site Editor edit is sitting in the database overriding it. WordPress can undo those overrides only one item at a time through buried menus, and some cleanups (rescanning pattern files, flushing the theme.json cache and OPcache) have no button anywhere.

The Workbench puts the whole development loop in one admin-bar menu:

* **Reset Site Editor overrides** for the active theme (templates, template parts, global styles) in one click
* **Rescan theme patterns**, clearing the pattern-file cache first so newly added pattern files actually appear
* **Flush every cache** that hides your changes: object cache, theme.json cache, OPcache
* **Clear all transients**

Safe by design: the Workbench is OFF by default and only appears in a development environment (`wp_get_environment_type()`), or when you opt in with `define( 'BLOCKWRIGHT_WORKBENCH', true );` in wp-config. Every tool is capability-gated and nonce-protected; the one destructive tool requires an explicit confirmation.

Pairs well with the official Create Block Theme plugin: use Create Block Theme to save the changes you want INTO your theme files, and the Workbench to clear out the state you do not want.

Works with any block theme.

== Installation ==

1. In your dashboard, go to Plugins, then Add New, then Upload Plugin.
2. Upload the Blockwright Workbench zip and click Install Now.
3. Click Activate.
4. On a site whose environment type is `local` or `development` (`wp_get_environment_type()`), the Workbench menu appears in the admin bar right away for users who can edit themes. Anywhere else, nothing is shown until you opt in by adding `define( 'BLOCKWRIGHT_WORKBENCH', true );` to wp-config.php.

== Frequently Asked Questions ==

= I do not see the Workbench menu =

That is the safety gate. It appears only when `wp_get_environment_type()` is `local` or `development`, or when wp-config defines `BLOCKWRIGHT_WORKBENCH` as true, and only for users with the `edit_theme_options` capability.

= Is this destructive? =

One tool does, on purpose: Reset Site Editor permanently deletes the database rows that override your theme's template, template part and global styles files, so the files win again. It bypasses the trash, so anything customised in the Site Editor and never exported to your theme is gone. It is marked destructive, requires a confirmation click, and never runs on a production site unless you explicitly enabled the Workbench there.

== Screenshots ==

1. The whole block-theme development loop in one admin-bar menu: reset all Site Editor overrides, rescan pattern files, flush every cache that hides your changes, and clear transients. Hidden outside a development environment, so it never shows on a client's live site.

== Roadmap ==

Blockwright Workbench stays focused on one thing: the block-theme development loop. On the way are more one-click cleanups and finer, preview-first resets that show you exactly what they will touch before they run. The single-site developer tools stay free and complete.

== Changelog ==

= 1.0.0 =
* Initial public release: four tools for block-theme development, off by default outside a development environment.
* Reset Site Editor overrides, rescan theme patterns, flush every cache, and clear all transients.
* Every tool is capability-gated and nonce-protected; Reset Site Editor requires an explicit confirmation.
