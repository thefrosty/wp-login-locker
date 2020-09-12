<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Actions;

use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpUtilities\Plugin\HooksTrait;

/**
 * Class NewUser
 * @package TheFrosty\WpLoginLocker\Actions
 */
class NewUser extends AbstractLoginLocker
{
    use GeoUtilTrait, HooksTrait;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('user_register', [$this, 'userRegisterAction']);
    }

    /**
     * Add default user meta on activation.
     *
     * @param int $user_id
     * @param string $ip_address
     */
    public static function addLoginUserMeta(int $user_id, string $ip_address): void
    {
        \add_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY, $ip_address, false);
        \add_user_meta($user_id, LoginLocker::LAST_LOGIN_TIME_META_KEY, \time(), false);
    }

    /**
     * On user registration, add their first unique meta of their IP address and login time.
     *
     * @param int $user_id The new users ID.
     */
    protected function userRegisterAction(int $user_id): void
    {
        self::addLoginUserMeta($user_id, $this->getIP());
    }
}
