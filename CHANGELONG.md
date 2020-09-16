# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.1.0 - 2020-09-15
- More settings to build complete email (with design).
- Add send test email.
- Bump WpSettingsApi to version `^3.2.2`

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
