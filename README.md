# DS Toolkit

Design Shop custom WordPress plugin. Houses all custom features for Design Shop builds.

## Author
Alipio Gabriel

## Plugin Structure
```
ds-toolkit/
├── ds-toolkit.php
├── includes/
│   ├── class-ds-toolkit.php
│   ├── class-ds-toolkit-updater.php
├── admin/
│   └── class-ds-toolkit-admin.php
├── features/
│   └── class-ds-login-branding.php
├── assets/
│   └── images/
│       └── cropped-LA-circle-logo-1.png
├── uninstall.php
└── README.md
```

## Features

### Enable LeagueApps Custom Login
Adds custom branding to the WordPress login page:
- Custom logo served from plugin assets
- "Powered by LeagueApps Design Shop" text under the logo
- Logo links to home URL, hover text uses site name
- "Need help? Visit Design Shop Academy" message below the login form

Toggle via: **WP Admin > DS Toolkit > Features > Enable LeagueApps Custom Login**

### Auto-Updates via GitHub
Uses native WordPress update hooks to check GitHub releases every 12 hours.
No external libraries or Composer required.
When a new release is published on GitHub, WordPress shows an Update button on the Plugins page.

---

## Changelog

### v0.4.1 - 2026-03-19
- Added native WordPress auto-updater via GitHub Releases API
- No external libraries, no Composer, no autoloader
- Uses wp_remote_get + pre_set_site_transient_update_plugins
- Checks for updates every 12 hours via WP transient cache

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
