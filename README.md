# WordPress Login Locker

![Login Locker](.github/wp-login-locker.jpg?raw=true "WordPress Login Locker")

[![PHP from Packagist](https://img.shields.io/packagist/php-v/thefrosty/wp-login-locker.svg)]()
[![Latest Stable Version](https://img.shields.io/packagist/v/thefrosty/wp-login-locker.svg)](https://packagist.org/packages/thefrosty/wp-login-locker)
[![Total Downloads](https://img.shields.io/packagist/dt/thefrosty/wp-login-locker.svg)](https://packagist.org/packages/thefrosty/wp-login-locker)
[![License](https://img.shields.io/packagist/l/thefrosty/wp-login-locker.svg)](https://packagist.org/thefrosty/wp-login-locker)
![Build Status](https://github.com/thefrosty/wp-login-locker/actions/workflows/main.yml/badge.svg)
[![codecov](https://codecov.io/gh/thefrosty/wp-login-locker/branch/develop/graph/badge.svg)](https://codecov.io/gh/thefrosty/wp-login-locker)

Disable direct access to your sites /wp-login.php script plus user notifications based on actions.

## Package Installation (via Composer)

To install this package, edit your `composer.json` file:
```json
{
    "require": {
        "thefrosty/wp-login-locker": "^2.10"
    }
}
```
Then run:

`$ composer install thefrosty/wp-login-locker`

Or, run:

`$ composer require thefrosty/wp-login-locker:^2.10`

## Getting into wp-login.php

Direct access to `/wp-login.php` is blocked unless the browser has a valid Login Locker cookie. The cookie is set
when the plugin is activated, and when a signed login link (valid for 7 days) is used. It lasts for the
**Cookie Expiration** and **Cookie Expiration Unit** settings (default 1 year). To get a login link:

- **Email me a login link:** enter your username or email address on the locked wp-login.php page. Requests are
  rate limited, and the page shows the same message whether or not the account exists.
- **New login emails:** each "new login" notification includes a login link.
- **WP-CLI:** `wp login-locker link <user>` prints a link for a user ID, username or email address. Add `--send`
  to email it to the user instead, or `--porcelain` to only output the link.

-----

- [Actions](#actions)
- [How to use actions](#how-to-use-actions)
- [Filters](#filters)
- [How to use filters](#how-to-use-filters)

