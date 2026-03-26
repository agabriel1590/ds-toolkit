# Changelog

All notable changes to DS Toolkit are documented here.

---

## [0.9.9.2-beta.1] - 2026-03-26
### Changed
- Updated MCP example prompts to showcase current feature set — content editing, taxonomy terms, BB layout switching, global colors, CSS editing, ACF field groups, ACF post types, and settings

## [0.9.9.1] - 2026-03-26
### Fixed
- WordPress "View version details" changelog tab now shows the full CHANGELOG.md history instead of only the latest GitHub release body

## [0.9.9] - 2026-03-26
### Added
- `DS_TOOLKIT_ADMIN_DOMAIN` constant — single place to configure the email domain gate; overridable via `wp-config.php`
- `bb_list_layout_templates` / `bb_apply_layout_template` MCP tools — switch Header Main, Footer Main or the front page to any DS Launchpad layout template
- ACF Field Group MCP tools: `acf_list_field_groups`, `acf_get_field_group`, `acf_create_field_group`, `acf_update_field_group`, `acf_delete_field_group`
- ACF Options Page MCP tools: `acf_list_options_pages`, `acf_create_options_page`, `acf_delete_options_page` (ACF Pro 6.2+)
- ACF Post Type & Taxonomy MCP tools: full CRUD via ACF Pro API
- LeagueApps email gate on all destructive/schema MCP tools

### Fixed
- `$is_beta` undefined variable in updater — beta-vs-stable comparison logic was silently never executing
- Extracted `leagueapps_gate()` helper — eliminates duplicated auth check blocks across toolkit settings, BB colors, and ACF schema tools

## [0.9.9-beta.4] - 2026-03-26
### Added
- `bb_list_layout_templates` MCP tool — list available DS Launchpad header/footer/home templates (Header Style 1–5, Footer Style 1–3, Home Page Layout 1–6)
- `bb_apply_layout_template` MCP tool — replace "Header Main" or "Footer Main" Themer layouts, or the site front page, with a DS Launchpad template; requires `confirm: true` as a destructive-action safeguard

## [0.9.9-beta.3] - 2026-03-26
### Added
- `acf_list_field_groups`, `acf_get_field_group`, `acf_create_field_group`, `acf_update_field_group`, `acf_delete_field_group` MCP tools — full CRUD for ACF Pro field groups (create with fields inline)
- `acf_list_options_pages`, `acf_create_options_page`, `acf_delete_options_page` MCP tools — manage ACF Pro options pages (requires ACF Pro 6.2+)

## [0.9.9-beta.2] - 2026-03-26
### Added
- `acf_list_post_types`, `acf_create_post_type`, `acf_update_post_type`, `acf_delete_post_type` MCP tools — full CRUD for ACF Pro post types
- `acf_list_taxonomies`, `acf_create_taxonomy`, `acf_update_taxonomy`, `acf_delete_taxonomy` MCP tools — full CRUD for ACF Pro taxonomies
- New **ACF Schema** toggle in MCP access controls (marked destructive, @leagueapps.com only)
### Security
- All destructive/schema tools (ACF Schema, BB colors, Toolkit Settings) now require `@leagueapps.com` email in addition to `manage_options`

## [0.9.9-beta.1] - 2026-03-26
### Added
- `get_bb_global_colors` MCP tool — read all Beaver Builder Global Style colors as a label → hex map
- `update_bb_global_colors` MCP tool — update named BB colors by label, flushes BB CSS cache automatically
- New **Beaver Builder** toggle in MCP access controls

## [0.9.8] - 2026-03-26
### Added
- `set_post_terms` MCP tool — assign or replace taxonomy terms on any post without editing content
- `terms` parameter on `create_post` and `update_post` — assign taxonomy terms in the same call as creating/updating a post
- `get_post` now returns current assigned terms grouped by taxonomy

## [0.9.7] - 2026-03-26
### Added
- Beta update channel — set `define( 'DS_TOOLKIT_UPDATE_CHANNEL', 'beta' )` in `wp-config.php` to receive pre-releases
- MCP per-group access controls — enable/disable Claude's access to Posts & Pages, CPTs, Taxonomies, ACF Fields, and Toolkit Settings
- New MCP tools: `list_post_types`, `list_taxonomies`, `list_terms`, `get_term`, `create_term`, `update_term`, `delete_term`, `get_post_fields`, `update_post_fields`
- ACF-aware field tools — uses `get_fields()` / `update_field()` when ACF active, falls back to `get_post_meta()`
### Changed
- All 365 university team logos converted from PNG to WebP (60% smaller)
### Fixed
- MCP config generator outputs correct `mcp-remote` stdio format for Claude Desktop
- MCP tab detects `WP_ENVIRONMENT_TYPE=local` and switches to `http://` URL for LocalWP
- Add `--allow-http` flag automatically when MCP URL is non-HTTPS
- "Check for Updates (beta channel)" button now correctly clears beta release cache and forces immediate update check

## [0.9.7-beta.1] - 2026-03-26
### Fixed
- "Check for Updates (beta channel)" button now correctly clears the beta release cache
- Force synchronous `wp_update_plugins()` before redirect so update appears immediately on plugins.php

## [0.9.6-beta.1] - 2026-03-26
### Changed
- Convert all 365 university team logos from PNG to WebP (60% size reduction, 16.4MB → 6.6MB)

## [0.9.5-beta.1] - 2026-03-25
### Added
- Beta update channel — set `define( 'DS_TOOLKIT_UPDATE_CHANNEL', 'beta' )` in `wp-config.php` to receive pre-releases
- "Check for Updates (beta channel)" label shown in plugin action links when on beta channel
- Separate transient cache per channel (`ds_toolkit_latest_release` vs `ds_toolkit_latest_release_beta`)

## [0.9.4] - 2026-03-24
### Added
- MCP per-group access controls — enable/disable Claude's access to Posts & Pages, CPTs, Taxonomies, ACF Fields, and Toolkit Settings independently from the MCP tab
- New MCP tools: `list_post_types`, `list_taxonomies`, `list_terms`, `get_term`, `create_term`, `update_term`, `delete_term`, `get_post_fields`, `update_post_fields`
- ACF-aware field tools — uses `get_fields()` / `update_field()` when ACF is active, falls back to `get_post_meta()`

## [0.9.3] - 2026-03-23
### Fixed
- Add `--allow-http` flag to mcp-remote args when MCP URL is `http://` (required for LocalWP environments)

## [0.9.2] - 2026-03-23
### Fixed
- MCP tab now detects `WP_ENVIRONMENT_TYPE=local` and switches URL to `http://` to avoid LocalWP SSL certificate issues with Node.js

## [0.9.1] - 2026-03-22
### Fixed
- Claude Desktop config generator now outputs correct `command/args` format using `mcp-remote` stdio proxy instead of unsupported `type: http` format

## [0.9.0] - 2026-03-22
### Added
- DS Toolkit MCP server — exposes WordPress as a self-contained MCP endpoint at `/wp-json/ds-toolkit/v1/mcp`
- MCP tab in admin settings with setup instructions, config generator, and available tools reference
- MCP tools: `list_posts`, `get_post`, `create_post`, `update_post`, `delete_post`
- Authentication via WordPress Application Passwords (WP 5.6+)

## [0.8.2] - 2026-03-15
### Changed
- Restrict DS Toolkit admin menu to `@leagueapps.com` email addresses only

## [0.8.1] - 2026-03-14
### Added
- `[child_pages]` shortcode — renders child pages as cards with configurable columns (desktop/tablet/mobile)

## [0.8.0] - 2026-03-10
### Added
- Global CSS tab — inject custom CSS sitewide with CodeMirror editor
- Global JS tab — inject custom JavaScript sitewide with CodeMirror editor

## [0.7.2] - 2026-03-05
### Changed
- Merged University Team Logo Finder into DS Toolkit as a dedicated tab (removed as standalone plugin)

## [0.7.1] - 2026-03-04
### Added
- All 365 university team logos
### Fixed
- Logo finder UI improvements

## [0.7.0] - 2026-03-03
### Added
- University Team Logo Finder tab — browse and import team logos into WP Media Library

## [0.6.6] - 2026-02-28
### Fixed
- `[getsubmenu]` shortcode — match original implementation behavior

## [0.6.5] - 2026-02-27
### Added
- `[getsubmenu]` shortcode — render submenus by location
- `[current_year]` shortcode
- Forminator Email Partner variable support

## [0.6.2] - 2026-02-20
### Fixed
- Feature defaults for existing installs (options not overwritten on update)

## [0.6.1] - 2026-02-18
### Added
- ACF Theme Options support in CSS Variables feature

## [0.6.0] - 2026-02-15
### Added
- Hide Beaver Builder Assistant feature

## [0.5.9] - 2026-02-10
### Changed
- Refactored to WordPress plugin standards and scalable class structure

## [0.5.8] - 2026-02-08
### Fixed
- Folder rename for fresh installs via Upload Plugin

## [0.5.7] - 2026-02-06
### Fixed
- Login logo only shown when Custom Login Branding is enabled

## [0.5.6] - 2026-02-04
### Fixed
- Updater folder detection and source renaming

## [0.5.5] - 2026-02-02
### Added
- Custom login logo picker via Media Library

## [0.5.4] - 2026-01-30
### Fixed
- Plugin folder naming via GitHub Actions zip

## [0.5.3] - 2026-01-28
### Changed
- Redesigned settings page UI

## [0.5.2] - 2026-01-26
### Added
- "Check for Updates" link on plugins page

## [0.5.1] - 2026-01-24
### Changed
- Moved DS Toolkit menu under Settings

## [0.4.2] - 2026-01-20
### Changed
- Enable login branding by default on activation

## [0.4.1] - 2026-01-18
### Changed
- Replaced PUC library with native WordPress updater (no external dependency)

## [0.4.0] - 2026-01-15
### Changed
- Stable release — removed auto-updater temporarily

## [0.2.1] - 2025-12-10
### Fixed
- Clean up `shop_name` key on uninstall

## [0.2.0] - 2025-12-05
### Added
- Login branding toggle in admin settings
- Custom login logo support
