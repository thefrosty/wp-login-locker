<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Actions;

use Dwnload\WpLoginLocker\AbstractLoginLocker;
use Dwnload\WpLoginLocker\LoginLocker;
use Dwnload\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpUtilities\Plugin\HooksTrait;

/**
 * Class NewUser
 * @package Dwnload\WpLoginLocker\Actions
 */
class NewUser extends AbstractLoginLocker
{
    use GeoUtilTrait, HooksTrait;

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->setRequest();
        $this->addAction('user_register', [$this, 'userRegisterAction']);
    }

    /**
     * On user registration, add their first unique meta of their IP address and login time.
     *
     * @param int $user_id The new users ID.
     */
    protected function userRegisterAction(int $user_id)
    {
        \add_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY, $this->getIP(), false);
        \add_user_meta($user_id, LoginLocker::LAST_LOGIN_TIME_META_KEY, \time(), false);
    }
}
