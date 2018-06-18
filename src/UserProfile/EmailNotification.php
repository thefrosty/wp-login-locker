<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\UserProfile;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class EmailNotification
 *
 * @package Dwnload\WpLoginLocker\UserProfile
 */
class EmailNotification extends UserProfile
{
    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction('show_user_profile', [$this, 'showExtraUserFields']);
        $this->addAction('edit_user_profile', [$this, 'showExtraUserFields']);
        parent::addHooks();
    }

    /**
     * Show extra user fields for last login IP and time.
     *
     * @param \WP_User|null $user
     */
    protected function showExtraUserFields(\WP_User $user = null)
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/user-profile/email-notification.php';
        echo \ob_get_clean();
    }
}
