<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

/**
 * Class LoginLocker
 *
 * @package Dwnload\WpLoginLocker
 */
final class LoginLocker
{
    const HOOK_PREFIX = 'login_locker/';
    const META_PREFIX = 'login_locker_';

    const LAST_LOGIN = self::META_PREFIX . 'user_last_login';
    const LAST_LOGIN_IP_META_KEY = self::LAST_LOGIN . '_ip';
    const LAST_LOGIN_TIME_META_KEY = self::LAST_LOGIN . '_time';

    const USER_EMAIL = self::META_PREFIX . 'user_email';
    const USER_EMAIL_META_KEY = self::USER_EMAIL . '_notification';

    const CONTAINER_REQUEST = 'request';
}
