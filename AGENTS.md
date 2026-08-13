# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

WordPress Login Locker is a plugin that disables direct access to `/wp-login.php` and provides user email notifications
based on login actions. It uses a plugin architecture pattern with the `TheFrosty\WpUtilities` framework.

## Development Commands

```bash
# Run all tests (PHP_CodeSniffer + PHPUnit)
composer tests

# Run PHP_CodeSniffer only
composer phpcs

# Run PHP Code Beautifier and Fixer only
composer phpcs:fix

# Run PHPUnit only
composer phpunit

# Run PHPUnit with HTML coverage report
composer phpunit:coverage

# Run Rector PHP refactoring tool
rector process src/

# Run Rector on tests
rector process tests/
```

## Architecture Overview

### Entry Point

- `wp-login-locker.php` - Main plugin file that bootstraps the plugin using `PluginFactory`
- All plugins/actions are registered via `$plugin->add()` and `$plugin->addOnHook()`

### Plugin Components (registered in wp-login-locker.php)

| Component                              | Path                                           | Purpose                                                                                     |
|----------------------------------------|------------------------------------------------|---------------------------------------------------------------------------------------------|
| `Actions\Login`                        | `src/Actions/Login.php`                        | Handles `wp_login` hook - sends email notifications, updates user meta (IP, timestamp)      |
| `Actions\Logout`                       | `src/Actions/Logout.php`                       | Secure logout via admin bar                                                                 |
| `Actions\NewUser`                      | `src/Actions/NewUser.php`                      | Handles `user_register` hook - initializes user meta                                        |
| `Login\WpLogin`                        | `src/Login/WpLogin.php`                        | Core authentication - validates auth cookies, blocks unauthorized access to `/wp-login.php` |
| `Login\Login`                          | `src/Login/Login.php`                          | Customizes login page styling (logo, header)                                                |
| `Settings`                             | `src/Settings/Settings.php`                    | Plugin settings page (general, login, email settings)                                       |
| `Admin\Menu`                           | `src/Admin/Menu.php`                           | Admin bar menu with secure logout link                                                      |
| `UserProfile\EmailNotificationSetting` | `src/UserProfile/EmailNotificationSetting.php` | User profile page email notification toggle                                                 |
| `UserProfile\LastLogin`                | `src/UserProfile/LastLogin.php`                | Displays last login info in user profile                                                    |
| `WpMail\WpMail`                        | `src/WpMail/WpMail.php`                        | Email template builder and sender                                                           |
| `Utilities\GeoUtilTrait`               | `src/Utilities/GeoUtilTrait.php`               | Gets IP address from various headers                                                        |
| `Utilities\UserMetaCleanup`            | `src/Utilities/UserMetaCleanup.php`            | Cron job to limit stored login history (max 10)                                             |

### Key Constants (`src/LoginLocker.php`)

- `HOOK_PREFIX = 'login_locker/'`
- `META_PREFIX = 'login_locker_'`
- `LAST_LOGIN_IP_META_KEY`, `LAST_LOGIN_TIME_META_KEY` - User meta keys
- `USER_EMAIL_META_KEY` - Email notification preference

### Authentication Flow

1. User visits `/wp-login.php`
2. `WpLogin::loginAuthCheck()` validates:
    - Auth cookie (encrypted with site URL as key)
    - `auth_check` GET parameter (one-time link sent via email)
    - Logout nonce verification
3. If no valid auth: returns 403 Forbidden with fake login page
4. If valid: allows login and sets long-lived auth cookie

### Settings Storage

Uses `dwnload/wp-settings-api` with three setting groups:

- `login_locker_general_settings` (lost password timeout, allow lost password)
- `login_locker_login_settings` (custom logo)
- `login_locker_email_settings` (email templates, disable emails toggle)

## Testing

Unit tests in `tests/unit/` use `WP_UnitTestCase` with the `TheFrosty\Tests\WpLoginLocker\TestCase` base class.

Test structure mirrors `src/`:

- `tests/unit/Actions/`
- `tests/unit/Login/`
- `tests/unit/Settings/`
- `tests/unit/UserProfile/`
- `tests/unit/Utilities/`

Run tests with:

```bash
composer phpunit
```

Make sure docker is running, and has a database container like "mysql:8.4", or "mariadb:11.5". To check running
containers use the command `docker ps`. If no database is running, run the following command: 
`docker run --restart unless-stopped -p 3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=wordpress_test mysql:8.4 --mysql-native-password=ON`

## Coding Standards

- PHP 8.4+ with strict types
- PSR-12 formatting
- WordPress coding standards (via WPCS)
- Slevomat coding standards (additional rules)
- Run `composer phpcs` to validate
- Run `composer phpcs:fix` to auto resolve phpcs issues with phpcbf command.

## Dependencies

- `thefrosty/wp-utilities` - Plugin framework
- `dwnload/wp-settings-api` - Settings API
- `symfony/http-foundation` - Request/Response objects

## Key File Locations

- Main plugin file: `wp-login-locker.php`
- Settings page: `wp-admin/admin.php?page=login_locker_settings`
- User profile: `wp-admin/profile.php#login-locker-settings`
- Email templates: `templates/email/`
- Login templates: `templates/login/`

## GitHub Workflows

- `main.yml` - CI tests (PHP_CodeSniffer, PHPUnit, Codecov coverage)
- `publish.yml` - Build release artifact on GitHub releases
- `security.yml` - PHP security checker
- `typos.yml` - Typos check

## Release Process

Releases are automatically triggered on GitHub release creation:

1. Build plugin ZIP (excludes dev files)
2. Upload as release asset
3. Duplicate asset for GitHub Updater compatibility
