<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker;

/**
 * Interface LoginLocker
 * @package TheFrosty\WpLoginLocker
 */
interface LoginLocker
{

    public const string HOOK_PREFIX = 'login_locker/';
    public const string META_PREFIX = 'login_locker_';

    public const string LAST_LOGIN = self::META_PREFIX . 'user_last_login';
    public const string LAST_LOGIN_IP_META_KEY = self::LAST_LOGIN . '_ip';
    public const string LAST_LOGIN_TIME_META_KEY = self::LAST_LOGIN . '_time';

    public const string USER_EMAIL = self::META_PREFIX . 'user_email';
    public const string USER_EMAIL_META_KEY = self::USER_EMAIL . '_notification';

    public const string CONTAINER_REQUEST = 'request';
    public const string WP_LOGIN = 'WpLogin';

    public const string CONTAINER_GITHUB_ARGS = 'github.config';
}
