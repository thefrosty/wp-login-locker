<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker;

/**
 * Interface LoginLocker
 * @package TheFrosty\WpLoginLocker
 */
interface LoginLocker
{

    public const HOOK_PREFIX = 'login_locker/';
    public const META_PREFIX = 'login_locker_';

    public const LAST_LOGIN = self::META_PREFIX . 'user_last_login';
    public const LAST_LOGIN_IP_META_KEY = self::LAST_LOGIN . '_ip';
    public const LAST_LOGIN_TIME_META_KEY = self::LAST_LOGIN . '_time';

    public const USER_EMAIL = self::META_PREFIX . 'user_email';
    public const USER_EMAIL_META_KEY = self::USER_EMAIL . '_notification';

    public const CONTAINER_REQUEST = 'request';
    public const WP_LOGIN = 'WpLogin';

    public const CONTAINER_GITHUB_ARGS = 'github.config';
}
