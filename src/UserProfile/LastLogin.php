<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\UserProfile;

/**
 * Class LastLogin
 *
 * @package Dwnload\WpLoginLocker\UserProfile
 */
class LastLogin extends UserProfile
{
    /**
     * Show extra user fields for last login IP and time.
     *
     * @param \WP_User|null $user
     */
    protected function showExtraUserFields(\WP_User $user = null)
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/user-profile/last-login.php';
        echo \ob_get_clean();
    }
}
