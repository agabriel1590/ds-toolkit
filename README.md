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

Enabled by default on activation. Toggle via: **WP Admin > DS Toolkit > Features**

### Auto-Updates via GitHub
Uses native WordPress update hooks to check GitHub releases every 12 hours.
No external libraries or Composer required.

---

## Changelog

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
