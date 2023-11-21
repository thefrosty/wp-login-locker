# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

## 2.3.1 - 2023-11-21

* Replace deprecated `FILTER_SANITIZE_STRING` in PHP >= 8.2 with `FILTER_SANITIZE_FULL_SPECIAL_CHARS`. [#78](https://github.com/thefrosty/wp-login-locker/pull/78)

## 2.3.0 - 2023-11-01

* Add support upto PHP 8.2.
* Drop support for PHP <= 7.4.

## 2.2.0 - 2023-07-23

* Feature/disable global emails.

## 2.1.11 - 2022-07-02

* Add encryption to login email.

## 2.1.10 - 2022-07-02

* Remove Login CSS attachment height/width requirements for `h1 a` background image logo.

## 2.1.9 - 2021-11-20

* Make sure `thefrosty/wp-utilties` is locked at `2.5.2`.

## 2.1.8 - 2021-11-20

* Make sure `thefrosty/wp-utilties` is locked at `2.5.1`.

## 2.1.7 - 2021-11-08
- Fix: PHP Fatal error: Uncaught TypeError: Argument 1 passed to 
`TheFrosty\WpLoginLocker\Login\LastLoginColumns::manageUsersCustomColumn()` must be of the type string, null given.

## 2.1.6 - 2021-11-07
- Bump glob-parent from 5.1.1 to 5.1.2 (#36)
- Bump hosted-git-info from 2.8.8 to 2.8.9 (#35)
- Bump lodash from 4.17.20 to 4.17.21 (#34)
- Bump path-parse from 1.0.6 to 1.0.7 (#37)
- Dependency updates (#38)
- PHP is now >=7.4.

## 2.1.4 - 2021-01-18
- Add asset icons for plugin updates.
- Update GitHub workflow (The `set-env` command is deprecated).
- Add XDEBUG_MODE for travis builds (change dist back to default `xenial`).

## 2.1.2 - 2020-09-21
- Update `dealerdirect/phpcodesniffer-composer-installer` requirement [#23](https://github.com/thefrosty/wp-login-locker/pull/23)
- Add `Release Asset` header to pull GitHub version from the release artifact.
- User release `$repo-$tag.zip` for the uploaded artifact.

## 2.1.1 - 2020-09-20
- Add `GitHub Plugin URI` plugin header, to allow updates direct from GitHub if you have the `GitHub Updater` plugin installed. 

## 2.1.0 - 2020-09-17
- More settings to build complete email (with design).
- Add send test email.
- Bump WpSettingsApi to version `^3.2.2`
- Update workflow to build a clean .zip of the plugin (without all the development files and directories).

## 2.0.0 - 2020-09-15
- Adding unit tests and code coverage.
- Bump PHP minimum requirement to >= 7.3.
- For full changes see [#14](https://github.com/thefrosty/wp-login-locker/pull/14) & 
[#15](https://github.com/thefrosty/wp-login-locker/pull/15)
- Remove helpers from composer autoload as `exit` was changed to `wp_die()` (and may not be available yet).
- Update all composer dependencies, including those from Dependabot.

## 1.3.1 - 2020-04-21
- Update Symfony HTTP Foundation

## 1.3.0 - 2020-03-01
- Add setting page.
    - Add login logo setting.
    - Add email pretext & message setting.

## 1.2.0 - 2020-02-29
- Update the email template, which should now not be so ipsum.

## 1.1.2 - 2019-07-19
- Bump symphony HTTP Foundation to ^4.3
- Bump WP Utilities ^1.7
- Move ignore plugin from inline to helper in WP Utilities.
- Update activation method to add user meta to the logged in user.

## 1.1.1 - 2019-07-19
- Moved from `dwnload` to `thefrosty` GitHub.
- Cleaned up CHANGELOG and README.

## 1.1.0 - 2019-07-19
- Updated code to use full text translation.
- Moved to PHP >= 7.2.
- Limit user IP & login time tracking to 10 saved entries.

## 1.0.0 - 2018-06-05
### Added
- Plucked this code out of core [dwnload](https://dwnload.io) feature.
