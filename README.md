# Yireo AdminAutoLogin

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-admin-auto-login)
<!-- badges.specs.end -->


**Automatically login a specified user without any JavaScript trick or weird redirect, but simply by spoofing the login mechanism**

**Obviously, don't use in production.**

## Usage
Install this module with your jedi skills.

Next, configure the username under which you want to login automatically:
```bash
magerun2 config:store:set system/admin/autologin_username exampleAdmin
```

It should now work.

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_AdminAutoLogin/compile.yml?label=compile)
<!-- badges.test.end -->
