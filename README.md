# Yireo AdminAutoLogin

**Automatically login a specified user without any JavaScript trick or weird redirect, but simply by spoofing the login mechanism**

> [!WARNING]
> This module logs anybody who can reach the Magento Admin Panel in as the configured admin user. It is meant for local
> development only. Never install it on a production or publicly reachable environment.

> [!IMPORTANT]
> Version 1.0.2 is a security release for the 1.0 branch (advisory `YIREO-ADMIN-AUTO-LOGIN-2026-001`). Upgrading to
> version 2.0.0 or higher is recommended: it moves the setting to `app/etc/env.php` and requires an explicit enabled flag.

## Installation
Install this module as a development dependency only:
```bash
composer require --dev yireo/magento2-admin-auto-login
bin/magento module:enable Yireo_AdminAutoLogin
bin/magento setup:upgrade
```

## Usage
Configure the username under which you want to login automatically:
```bash
bin/magento config:set system/admin/autologin_username exampleAdmin
```

Optionally, restrict the allowed client IPs in your `app/etc/env.php`:
```php
'yireo_admin_autologin' => [
    'allowed_ips' => ['127.0.0.1', '::1', '172.16.0.0/12'],
],
```

The auto-login only happens when all of the following conditions are met:
- Magento runs in developer mode (`bin/magento deploy:mode:set developer`);
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
- Behind a reverse proxy, Magento might see the private IP of the proxy, which is allowed by default. Set `allowed_ips`
  explicitly in that case;
- When you stop using this module, remove the setting with `bin/magento config:delete system/admin/autologin_username`.
