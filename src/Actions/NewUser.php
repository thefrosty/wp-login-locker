<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Actions;

use Dwnload\WpLoginLocker\LoginLocker;
use Dwnload\WpLoginLocker\RequestsInterface;
use Dwnload\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpUtilities\Plugin\HooksTrait;

/**
 * Class NewUser
 * @package Dwnload\WpLoginLocker\Actions
 */
class NewUser extends AbstractHookProvider implements RequestsInterface
{
    use GeoUtilTrait, HooksTrait;

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $this->getPlugin()->getContainer()->get(LoginLocker::CONTAINER_REQUEST);
        $this->setRequest($request);
        $this->addAction('user_register', [$this, 'userRegisterAction']);
    }

    /**
     * On user registration, add their first unique meta of their IP address and login time.
     *
     * @param int $user_id The new users ID.
     */
    protected function userRegisterAction(int $user_id)
    {
        \add_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY, $this->getIP(), true);
        \add_user_meta($user_id, LoginLocker::LAST_LOGIN_TIME_META_KEY, \time(), true);
    }
}
