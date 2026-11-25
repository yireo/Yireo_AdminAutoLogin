# Yireo AdminAutoLogin

<!-- badges.specs.start -->
<div class="github-specs-badges">

![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-admin-auto-login)

</div>
<!-- badges.specs.end -->


**Automatically login a specified user without any JavaScript trick or weird redirect, but simply by spoofing the login mechanism**

> [!WARNING]
> This module logs anybody who can reach the Magento Admin Panel in as the configured admin user. It is meant for local
> development only. Never install it on a production or publicly reachable environment.

## Installation
Install this module as a development dependency only:
```bash
composer require --dev yireo/magento2-admin-auto-login
bin/magento module:enable Yireo_AdminAutoLogin
bin/magento setup:upgrade
```

## Usage
Add the following to your `app/etc/env.php`:
```php
'yireo_admin_autologin' => [
    'enabled' => true,
    'username' => 'exampleAdmin',
    // Optional, defaults to localhost and private network ranges
    'allowed_ips' => ['127.0.0.1', '::1', '172.16.0.0/12'],
],
```

The auto-login only happens when all of the following conditions are met:
- Magento runs in developer mode (`bin/magento deploy:mode:set developer`);
- `enabled` is set to the boolean `true` in `app/etc/env.php`;
- The client IP matches one of the `allowed_ips` (single IPs or CIDR ranges, as array or comma-separated string).
  When `allowed_ips` is not set, `127.0.0.0/8`, `::1`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16` and `fc00::/7`
  are allowed. An empty array blocks all IPs;
- The configured admin user exists, is active and is not locked.

The login follows the regular Magento login flow: the session ID is regenerated, the admin session is registered by
`Magento_Security` and the event `backend_auth_user_login_success` is dispatched. Every auto-login is logged as a
warning in `var/log/system.log`.

Notes:
- Two-Factor Authentication (`Magento_TwoFactorAuth`) still applies, when enabled;
- The login and logout pages are skipped. After logging out, the next Admin Panel page logs you in again;
- When secret keys in URLs are enabled, the first page after auto-login redirects to the Admin startup page.

## Upgrading from 1.0
The setting `system/admin/autologin_username` in the database (`core_config_data`) is no longer used, because a
database setting can be copied to other environments by accident. Move the username to `app/etc/env.php` (see above)
and remove the old setting:
```bash
bin/magento config:delete system/admin/autologin_username
```

## Current status

<!-- badges.test.start -->
<div class="github-pipeline-badges">

![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/compile.yml?label=compile)

</div>
<!-- badges.test.end -->
