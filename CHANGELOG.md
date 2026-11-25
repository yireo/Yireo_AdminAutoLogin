# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 25 November 2026
### Added
- README now documents the `app/etc/env.php` configuration and installing the module as a dev-only dependency (`composer require --dev`)

### Changed
- BREAKING: Auto-login settings moved from the database (`system/admin/autologin_username`) to `app/etc/env.php` (`yireo_admin_autologin` with `enabled`, `username` and `allowed_ips`), so they can no longer be copied to other environments by accident; remove the old setting with `bin/magento config:delete system/admin/autologin_username`
- BREAKING: Auto-login now only happens when Magento runs in developer mode and the client IP is on an allowlist (localhost and private networks by default)
- Auto-login now follows the regular Magento login flow (new session ID, Magento_Security admin session, `backend_auth_user_login_success` event) and every auto-login is logged in `var/log/system.log`
- Dropped PHP 8.1 support, added PHP 8.5 support

### Fixed
- Inactive, locked or non-existing admin users are no longer logged in automatically
- PHP deprecation warning from `trim()` in newer PHP versions

## [1.0.1] - 26 May 2023
### Added
- Fix wrong variable name #2 (@nenes25)

## [1.0.0] - March 2022
### Added
- Initial public release
