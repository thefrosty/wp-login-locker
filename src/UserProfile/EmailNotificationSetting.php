<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\UserProfile;

use TheFrosty\WpLoginLocker\LoginLocker;

/**
 * Class EmailNotification
 *
 * @package TheFrosty\WpLoginLocker\UserProfile
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
    public function addHooks(): void
    {
        $this->addAction(parent::USER_PROFILE_HOOK, [$this, 'showExtraUserFields']);
        parent::addHooks();
    }

    /**
     * Show extra user fields for last login IP and time.
     *
     * @param \WP_User|null $user
     */
    protected function showExtraUserFields(\WP_User $user = null): void
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/user-profile/email-notification.php';
        echo \ob_get_clean();
    }
}
