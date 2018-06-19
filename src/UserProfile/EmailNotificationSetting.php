<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\UserProfile;

use Dwnload\WpLoginLocker\LoginLocker;

/**
 * Class EmailNotification
 *
 * @package Dwnload\WpLoginLocker\UserProfile
 */
class EmailNotificationSetting extends UserProfile
{
    /**
     * EmailNotification constructor.
     */
    public function __construct()
    {
        $this->fields = [
            LoginLocker::USER_EMAIL_META_KEY,
        ];
    }

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction(parent::USER_PROFILE_HOOK, [$this, 'showExtraUserFields']);
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
