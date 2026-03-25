# DS Toolkit

Design Shop custom WordPress plugin. Houses all custom features for Design Shop builds.

## Author
Alipio Gabriel

## Plugin Structure
```
ds-toolkit/
├── ds-toolkit.php
├── uninstall.php
├── includes/
│   ├── class-ds-toolkit.php
│   └── class-ds-toolkit-updater.php
├── admin/
│   ├── class-ds-toolkit-admin.php
│   └── views/
│       └── page-settings.php
├── features/
│   └── class-ds-login-branding.php
└── assets/
    ├── css/
    │   ├── admin.css
    │   └── login.css
    ├── js/
    │   ├── admin.js
    │   └── login.js
    └── images/
        └── cropped-LA-circle-logo-1.png
```

## Features

### Enable LeagueApps Custom Login
Adds custom branding to the WordPress login page:
- Custom logo served from plugin assets
- "Powered by LeagueApps Design Shop" text under the logo
- Logo links to home URL, hover text uses site name
- "Need help? Visit Design Shop Academy" message below the login form

Enabled by default on activation. Toggle via: **WP Admin > DS Toolkit > Features**

### Auto-Updates via GitHub
Uses native WordPress update hooks to check GitHub releases every 12 hours.
No external libraries or Composer required.

---

## Changelog

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
