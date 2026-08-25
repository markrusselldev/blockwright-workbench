# Changelog

Dev-facing changelog for the Blockwright Workbench plugin, following the Keep a
Changelog convention (keepachangelog.com): fixes and features accumulate under
[Unreleased] AS THEY LAND; at each version bump the section is renamed to the
new version + date and its user-facing entries are copied into `readme.txt`'s
changelog (the wp.org-facing record). This file is dev tooling - the release
zip whitelist excludes it.

**Do not bump the version while developing.** wp.org's SVN is a RELEASE
repository, not a development one, so the number in
`blockwright-workbench.php` and `readme.txt`'s `Stable tag` stays put until the
day a release actually ships. Bumping early pre-commits to a number before you
know what lands.

**Awaiting first submission to wp.org.** The procedure is
the `wporg-submit` skill; the per-package checklist is
`the project notes` section 4.

## [Unreleased]

Nothing yet.

## [1.0.0] - 2026-08-07

Initial public release. Tooling parity with the other packages, guideline
self-audit, readme validated, and a runtime Plugin Check pass on the shipped
artifact.

### Added

- Bulk reset of all Site Editor overrides (templates, template parts, global
  styles) - the file-to-database drift fix, and the only destructive tool.
- Pattern rescan that clears WordPress's pattern-file cache first, so newly added
  pattern FILES actually appear.
- One-click flush of the object cache, the theme.json cache and OPcache.
- Clear all transients.
