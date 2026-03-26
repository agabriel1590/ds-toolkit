# DS Toolkit

A professional WordPress plugin for Design Shop (LeagueApps) builds. Bundles all recurring custom features — shortcodes, admin tools, branding, CSS/JS management, and a built-in MCP server so Claude can communicate directly with WordPress — into a single, self-updating plugin.

Visible only to `@leagueapps.com` users in WP Admin.

## Author
Alipio Gabriel

---

## Requirements
- WordPress 5.6+
- PHP 7.4+
- HTTPS (live sites) — or `define('WP_ENVIRONMENT_TYPE', 'local')` for LocalWP
- Node.js / npx — required on developer machines for the Claude Desktop MCP connection

---

## Plugin Structure
```
ds-toolkit/
├── ds-toolkit.php                          — Entry point, constants, bootstrap
├── uninstall.php                           — Cleanup on deletion
├── includes/
│   ├── class-ds-toolkit.php                — Core loader, feature registry, defaults
│   ├── class-ds-toolkit-updater.php        — GitHub Releases auto-updater
│   └── defaults/
│       ├── global-css.css                  — Default LaunchPad 4 CSS baseline
│       └── global-js.js                    — Default SiteUI JS (sticky header, etc.)
├── admin/
│   ├── class-ds-toolkit-admin.php          — Admin menu, tabs, settings registration
│   ├── class-ds-logo-finder.php            — University logo finder AJAX + assets
│   ├── class-ds-mcp-server.php             — MCP JSON-RPC server (REST endpoint)
│   └── views/
│       ├── page-settings.php               — Features tab
│       ├── page-logo-finder.php            — University Logo Finder tab
│       ├── page-global-css.php             — Global CSS tab
│       ├── page-global-js.php              — Global JS tab
│       └── page-mcp.php                    — MCP tab (config generator + tools reference)
├── features/
│   ├── class-ds-login-branding.php         — Custom WP login page branding
│   ├── class-ds-hide-fl-assistant.php      — Hide BB FL Assistant for non-LeagueApps users
│   ├── class-ds-acf-css-vars.php           — ACF options fields → CSS custom properties
│   ├── class-ds-getsubmenu.php             — [getsubmenu] shortcode
│   ├── class-ds-current-year.php           — [current_year] shortcode
│   ├── class-ds-forminator-email-partner.php — Forminator {email_partner} variable
│   ├── class-ds-global-css.php             — Injects Global CSS into <head>
│   ├── class-ds-global-js.php              — Injects Global JS before </body>
│   └── class-ds-child-pages.php            — [child_pages] + [get_parent_page_title] shortcodes
└── assets/
    ├── css/
    │   ├── admin.css                        — All admin UI styles
    │   ├── logo-finder.css                  — Logo finder grid + import bar
    │   ├── child-pages.css                  — Responsive card grid
    │   └── login.css                        — Custom login page styles
    ├── js/
    │   ├── admin.js                         — Admin toggles, media picker, mapping table
    │   └── logo-finder.js                   — Live search, multi-select, AJAX import
    └── images/
        ├── cropped-LA-circle-logo-1.png     — Default login logo
        └── team_logos/                      — 365 university team logo PNGs
```

---

## Features

### LeagueApps Custom Login Branding
Replaces the default WordPress login page with LeagueApps / Design Shop branding.
- Custom logo (default: LeagueApps circle logo, or pick any image from Media Library)
- "Powered by LeagueApps Design Shop" tagline
- Logo links to home URL; "Need help? Visit Design Shop Academy" link below the form
- Toggle and logo picker under **Settings → DS Toolkit → Features**

### Hide Beaver Builder FL Assistant
Hides the Beaver Builder Cloud / FL Assistant toolbar button for anyone not logged in with a `@leagueapps.com` email — keeps the admin bar clean for clients.

### ACF Theme Options → CSS Variables
Maps ACF options page fields to CSS custom properties output in `:root` on every front-end page.
- Add/remove mappings via the dynamic table in the Features tab
- Optional fallback value per mapping
- Default mapping: `header_scrolled_bar_color` → `--header-scrolled-bar-color`

### [getsubmenu] Shortcode
Outputs a navigation list of child pages or sub-menu items.
```
[getsubmenu listfrom="About Us" mode="pages"]
[getsubmenu listfrom="Main Menu" mode="menus"]
```
- `mode="pages"` — resolves parent by ID, slug, path, or title; lists published child pages
- `mode="menus"` — finds a top-level nav menu item matching `listfrom`; lists its direct children
- Output: `<div class="submenu-text">` with links separated by `<br />`

### [current_year] Shortcode
Outputs the current 4-digit year. Auto-updates — no maintenance needed.
```
© [current_year] LeagueApps
```

### Forminator {email_partner} Variable
Injects a custom `{email_partner}` merge tag into Forminator forms, resolved from the ACF `partner_email` options field. Configurable fallback email in the Features tab.

### Global CSS
Site-wide CSS injected into `<head>` at priority 99 (after all theme styles).
- Full LaunchPad 4 CSS baseline pre-loaded on activation
- Edit directly in the admin with CodeMirror syntax highlighting
- Toggle on/off without losing content
- Tab: **Settings → DS Toolkit → Global CSS**

### Global JS
Site-wide JavaScript injected before `</body>` at priority 99.
- Pre-loaded with SiteUI JS: sticky header, clickable columns, equal heights, button normaliser
- CodeMirror editor with JS syntax highlighting
- Toggle on/off without losing content
- Tab: **Settings → DS Toolkit → Global JS**

### [child_pages] Shortcode
Renders child pages of the current page as a responsive card grid using a Beaver Builder saved layout template.
```
[child_pages]
[child_pages template="56369" columns="4" columns_tablet="2" columns_mobile="1"]
```
- Default template ID and column counts configurable in the Features tab
- BB template reads child page title and permalink dynamically via `setup_postdata()`
- Responsive grid via CSS custom properties (`--dst-cols`, `--dst-cols-tablet`, `--dst-cols-mobile`)

### [get_parent_page_title] Shortcode
Used inside the BB card template — outputs the title of the page where `[child_pages]` is placed.

### University Logo Finder
Browse and import 365 university team logos directly into the WordPress Media Library.
- Live search by university name or mascot
- Click to select logos (checkmark overlay); Select All / Clear
- Floating import bar with selection count
- Sequential AJAX import with real-time progress log
- Duplicate detection — skips logos already in the Media Library
- Tab: **Settings → DS Toolkit → University Logo Finder**

### MCP Server (Claude ↔ WordPress)
DS Toolkit registers a [Model Context Protocol](https://modelcontextprotocol.io) endpoint so Claude can read and edit WordPress content without any additional plugins.

**Endpoint:** `https://yoursite.com/wp-json/ds-toolkit/v1/mcp`
**Auth:** WordPress Application Passwords (WP 5.6+ built-in)
**Protocol:** MCP JSON-RPC 2.0 (`2024-11-05`)

**Available tools:**

| Tool | Description | Requires |
|---|---|---|
| `list_posts` | List posts/pages with filters | edit_posts |
| `get_post` | Get full content of a post by ID | edit_posts |
| `create_post` | Create a new post or page | publish_posts |
| `update_post` | Edit title, content, excerpt, or status | edit_post |
| `delete_post` | Trash or permanently delete a post | delete_post |
| `get_toolkit_settings` | Read all DS Toolkit feature settings | manage_options |
| `update_toolkit_settings` | Change feature toggles, CSS, JS, columns, etc. | manage_options |

**Claude Desktop setup** (requires Node.js):
```json
{
  "mcpServers": {
    "ds-toolkit": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "https://yoursite.com/wp-json/ds-toolkit/v1/mcp",
        "--header",
        "Authorization:Basic <base64 of username:app_password>"
      ]
    }
  }
}
```

Use the **MCP tab** in Settings → DS Toolkit for a guided setup with an auto-generated config.

### Auto-Updates via GitHub
Checks GitHub Releases every 12 hours via native WordPress update hooks. Update prompt appears in **WP Admin → Plugins** like any other plugin — no Composer, no external libraries.

---

## Changelog

### v0.9.3 - 2026-03-26
- Fixed MCP local connection: `mcp-remote` blocks non-HTTPS URLs unless `--allow-http` is passed. Config generator now automatically adds `--allow-http` when the endpoint URL is `http://` (LocalWP)

### v0.9.2 - 2026-03-26
- Fixed MCP on LocalWP: Node.js does not trust LocalWP's local SSL certs so `mcp-remote` would fail with an SSL error on `https://`. When `WP_ENVIRONMENT_TYPE=local` is detected, the MCP tab now shows and generates the config using `http://` instead, which works because Application Passwords are already unlocked for local environments
- MCP tab status bar shows "Local" badge and explains the http/https switch when on a local environment

### v0.9.1 - 2026-03-26
- Fixed MCP config generator: Claude Desktop requires `mcp-remote` as a stdio proxy — direct `url` format only works in Claude Code. Generated config now outputs the correct `command/args` format using `npx mcp-remote`

### v0.9.0 - 2026-03-26
- Added DS Toolkit as a self-contained MCP (Model Context Protocol) server — no extra plugins required
- Registers `/wp-json/ds-toolkit/v1/mcp` REST endpoint that speaks MCP JSON-RPC 2.0 (protocol `2024-11-05`)
- Authenticated via WordPress Application Passwords (built into WP 5.6+) — no login password exposed
- Exposed tools: `list_posts`, `get_post`, `create_post`, `update_post`, `delete_post`, `get_toolkit_settings`, `update_toolkit_settings`
- Added **MCP tab** in DS Toolkit admin (Settings > DS Toolkit > MCP):
  - Live status indicator showing whether the endpoint is active
  - Step-by-step setup instructions with direct link to Application Passwords in profile
  - Config generator — enter username + app password, get the exact JSON for Claude Desktop and CLI command for Claude Code
  - Full list of available tools with required capability for each

### v0.8.2 - 2026-03-26
- DS Toolkit menu (Settings > DS Toolkit) is now only visible to users with a @leagueapps.com email address
- Non-LeagueApps users with manage_options capability will not see the menu or be able to access the settings page

### v0.8.1 - 2026-03-26
- Added [child_pages] shortcode — renders child pages of the current page as a responsive card grid using a Beaver Builder saved layout template
- Added [get_parent_page_title] shortcode — outputs the title of the page where [child_pages] is placed; use inside the BB card template
- Configurable defaults in Settings > DS Toolkit: BB template ID, desktop/tablet/mobile columns
- All settings overridable per shortcode: [child_pages template="56369" columns="4" columns_tablet="2" columns_mobile="1"]
- BB reads child page title and permalink dynamically via setup_postdata() per loop iteration
- Responsive grid uses CSS custom properties (--dst-cols, --dst-cols-tablet, --dst-cols-mobile)

### v0.8.0 - 2026-03-26
- Added Global CSS tab — edit and toggle site-wide CSS injected into <head> on every page
- Added Global JS tab — edit and toggle site-wide JS injected before </body> on every page
- Both editors use WordPress CodeMirror with syntax highlighting (CSS/JS modes)
- Default LaunchPad 4 CSS and JS pre-seeded on fresh activations and existing installs
- Defaults stored in includes/defaults/ for easy maintenance
- Tab structure is now: Features | University Logo Finder | Global CSS | Global JS

### v0.7.2 - 2026-03-26
- Merged University Logo Finder into DS Toolkit settings page as a tab (Features | University Logo Finder)
- Removed separate "Team Logos" submenu — everything lives under Settings > DS Toolkit
- Logo finder assets only load on the logos tab (features tab unaffected)
- Fixed version mismatch on update — version is now bumped before tagging so the plugin zip always contains the correct version number

### v0.7.1 - 2026-03-26
- Fixed: all 365 university team logo PNGs now included in the plugin package (were missing from v0.7.0)
- Fixed: logo image URLs now built in PHP using rawurlencode() so filenames with spaces load correctly
- Redesigned card UI: each card shows logo image → university name → Select button
- Select button toggles to "Selected ✓" with blue fill when active
- Floating import bar animates up from the bottom when any logos are selected

### v0.7.0 - 2026-03-26
- Added University Team Logo Finder — dedicated admin tool at Settings > Team Logos
- Search 365 university logos by name or mascot with live filtering
- Multi-select logos via click (checkmark overlay), Select All / Clear controls
- Import selected logos directly into the WordPress Media Library via WP sideload API
- Duplicate detection — skips logos already in the Media Library with a warning
- Real-time import progress log with color-coded status (imported / already exists / error)
- Sticky import footer bar shows selection count and import button

### v0.6.6 - 2026-03-26
- Fixed [getsubmenu] shortcode — rewrote to match original functions.php implementation
- Correct attributes: listfrom (page title/slug/ID) and mode (pages or menus)
- pages mode: resolves parent by numeric ID, path, sanitized slug, or page title; lists published child pages
- menus mode: scans all nav menus for a top-level item matching listfrom, lists its direct children
- Output: <div class="submenu-text"> with <br /> between links (matching original behavior)

### v0.6.5 - 2026-03-26
- Added [current_year] shortcode — outputs the current year, auto-updates every year, no maintenance needed
- Added Forminator {email_partner} variable — replaces {email_partner} in Forminator forms with the ACF partner_email options field
- Forminator fallback email is configurable in Settings > DS Toolkit (defaults to designshop@leagueapps.com)
- Both features enabled by default on activation and for existing installs via maybe_set_defaults()

### v0.6.4 - 2026-03-26
- Added [getsubmenu] shortcode feature — outputs child pages of any page as a navigation list
- Supports three modes: current page (no args), by page ID (`id="42"`), or by page slug (`parent="about-us"`)
- Toggleable via Settings > DS Toolkit, enabled by default
- Settings card includes plain-English usage examples for each mode

### v0.6.3 - 2026-03-26
- Fixed: "new version available" notice showing even when plugin is up to date
- Updater now adds plugin to $transient->no_update when current, clearing any stale response entry

### v0.6.2 - 2026-03-26
- Fixed: feature defaults now apply to existing installs, not just fresh activations
- Added maybe_set_defaults() — runs on every load, fills in any missing settings keys so updates automatically enable new features with correct pre-filled values
- Centralised all defaults into get_defaults() shared by both activate() and maybe_set_defaults()

### v0.6.1 - 2026-03-26
- Added "ACF Theme Options → CSS Variables" feature with dynamic mapping table UI
- Map any ACF options field to a CSS custom property output in :root on every page
- Supports optional fallback values per mapping
- Pre-seeded with default mapping: header_scrolled_bar_color → --header-scrolled-bar-color (fallback: var(--fl-global-accent))
- All new features (hide_fl_assistant, acf_css_vars) now default to enabled on activation

### v0.6.0 - 2026-03-26
- Added "Hide Beaver Builder Cloud Icon for Non-LeagueApps Users" feature
- Hides the FL Assistant button in the BB toolbar for all users except @leagueapps.com accounts
- Toggleable via Settings > DS Toolkit

### v0.5.9 - 2026-03-26
- Standardised plugin headers (added Requires at least, Tested up to, Requires PHP, License URI, Domain Path)
- Removed function_exists() guards from main file — not needed for plugin-private functions
- Moved activation logic to DS_Toolkit::activate() static method
- Added is_admin() guard in core loader — admin class and updater no longer load on front end
- Introduced feature registry pattern in DS_Toolkit — adding a new feature only requires a class file and one array entry
- Extracted all admin CSS/JS from inline PHP into assets/css/admin.css and assets/js/admin.js
- Extracted settings page HTML into admin/views/page-settings.php
- Extracted login page CSS/JS into assets/css/login.css and assets/js/login.js
- Settings are now read once in the core loader and passed to feature classes via constructor
- Hardcoded Academy URL moved to DS_Login_Branding::ACADEMY_URL constant
- Fixed uninstall.php — now fully removes ds_toolkit_settings and ds_toolkit_latest_release transient
- Removed unused composer.json and vendor/ directory (PUC library replaced in v0.4.1)

### v0.5.8 - 2026-03-25
- Fixed folder rename to also apply on fresh installs via Upload Plugin, not just auto-updates

### v0.5.7 - 2026-03-25
- Login Logo option is now hidden when LeagueApps Custom Login is disabled, and shown when enabled

### v0.5.6 - 2026-03-25
- Fixed updater to detect the plugin using its actual folder name via plugin_basename() — update button now shows regardless of folder name
- Fixed fix_source_dir to confirm plugin identity by checking for ds-toolkit.php inside the zip, not by matching the folder name
- Both fixes ensure the plugin always installs into ds-toolkit/ on every future update

### v0.5.5 - 2026-03-25
- Added custom login logo picker — select any image from the Media Library to replace the default logo
- Falls back to the default LeagueApps logo if no custom logo is set

### v0.5.4 - 2026-03-25
- Added GitHub Actions workflow to attach a correctly-named ds-toolkit.zip to every release
- Updater now uses the release asset zip (correct folder name) instead of the raw GitHub zipball

### v0.5.3 - 2026-03-25
- Redesigned settings page UI with dark header, feature cards, toggle switches, and footer meta

### v0.5.2 - 2026-03-25
- Added "Check for Updates" link on the Plugins page to force an immediate update check

### v0.5.1 - 2026-03-25
- Moved DS Toolkit menu from top-level admin menu to Settings > DS Toolkit

### v0.5.0 - 2026-03-25
- Fixed GitHub auto-updater: renamed extracted zip folder to match plugin slug so updates install correctly

### v0.4.2 - 2026-03-19
- Enable LeagueApps Custom Login is now ON by default when plugin is first activated

### v0.4.1 - 2026-03-19
- Added native WordPress auto-updater via GitHub Releases API
- No external libraries, no Composer, no autoloader

### v0.4.0 - 2026-03-19
- Stable build, removed PUC auto-updater
- Kept function_exists() guard on ds_toolkit_run()

### v0.2.1 - 2026-03-19
- Removed Shop Name field from admin General Settings

### v0.2.0 - 2026-03-19
- Added Enable LeagueApps Custom Login toggle
- Custom login logo, branding, and support link

### v0.1.0 - 2026-03-19
- Initial plugin boilerplate
- Admin settings page
