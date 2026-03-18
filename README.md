# DS Toolkit

Design Shop custom WordPress plugin. Houses all custom features for Design Shop builds.

## Author
Alipio Gabriel

## Plugin Structure
```
ds-toolkit/
├── ds-toolkit.php
├── includes/
│   └── class-ds-toolkit.php
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

---

## Changelog

### v0.4.0 - 2026-03-19 (Stable)
- Removed auto-updater (PUC) entirely due to activation errors
- Reverted to clean stable build
- Kept function_exists() guard on ds_toolkit_run()

### v0.2.1 - 2026-03-19
- Removed Shop Name field from admin General Settings
- Updated uninstall.php to clean up shop_name option key

### v0.2.0 - 2026-03-19
- Added Features section in admin settings page
- Added Enable LeagueApps Custom Login toggle
- Custom login logo stored in plugin assets
- Powered by LeagueApps Design Shop branding under logo
- Support link below login form

### v0.1.0 - 2026-03-19
- Initial plugin boilerplate
- Admin settings page
