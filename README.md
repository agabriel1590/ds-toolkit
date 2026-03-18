# DS Toolkit

Design Shop custom WordPress plugin. Houses all custom features for Design Shop builds.

## Author
Alipio Gabriel

## Plugin Structure
```
ds-toolkit/
├── ds-toolkit.php
├── composer.json
├── .gitignore
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
├── vendor/
│   └── yahnis-elsts/
│       └── plugin-update-checker/
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
Uses Plugin Update Checker (PUC v5.6) to detect new GitHub releases automatically. WordPress will show an update notice on the Plugins page when a new version is released.

---

## Changelog

### v0.3.2 - 2026-03-19
- Fixed fatal error caused by duplicate function declaration when multiple plugin versions exist
- Wrapped ds_toolkit_run() in function_exists() check

### v0.3.1 - 2026-03-19
- Fixed fatal error on plugin activation caused by incorrect PUC bootstrap
- Now uses load-v5p6.php as the PUC entry point for correct autoloading
- Added file_exists check before loading PUC to prevent fatal errors

### v0.3.0 - 2026-03-19
- Added Plugin Update Checker (PUC v5.6) for automatic WordPress plugin updates via GitHub releases
- Added composer.json and .gitignore
- Bundled PUC vendor files (no Composer required on server)

### v0.2.1 - 2026-03-19
- Removed Shop Name field from admin General Settings
- Updated uninstall.php to clean up shop_name option key

### v0.2.0 - 2026-03-19
- Added Features section in admin settings page
- Added "Enable LeagueApps Custom Login" toggle
- Custom login logo stored in plugin assets
- "Powered by LeagueApps Design Shop" branding under logo
- Support link below login form

### v0.1.0 - 2026-03-19
- Initial plugin boilerplate
- Admin settings page
