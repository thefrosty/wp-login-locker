<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Plugins\WpUserProfiles;

use Dwnload\WpLoginLocker\LoginLocker;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class UserEmailSection
 * @package Dwnload\WpLoginLocker\Plugins\WpUserProfiles
 */
class UserEmailSection implements PluginAwareInterface, WpHooksInterface
{
    use HooksTrait, PluginAwareTrait;

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction('add_meta_boxes', [$this, 'addMetaBox'], 10, 2);
    }

    /**
     * Add the meta boxes for this section.
     *
     * @param  string $type
     * @param  null|\WP_User $user
     */
    protected function addMetaBox($type = '', $user = null)
    {
        \add_meta_box(
            'wp-login-locker-notifications',
            \esc_attr_x('Emails', 'users user-admin edit screen', 'wp-login-locker'),
            function (\WP_User $user = null) {
                $this->emailsMetaboxCallback($user);
            },
            $type,
            'normal',
            'high',
            $user
        );
    }

    /**
     * @param \WP_User|null $user
     */
    private function emailsMetaboxCallback(\WP_User $user = null)
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/metaboxes/user-profile-emails.php';
        echo \ob_get_clean();
    }

    /**
     * Save section data
     *
     * @param \WP_User|null $user
     */
    public function save($user = null)
    {
        if (isset($_POST[self::USER_EMAIL_META_KEY])) {
            \update_user_meta($user->ID, self::USER_EMAIL_META_KEY, true);
        } else {
            \delete_user_meta($user->ID, self::USER_EMAIL_META_KEY);
        }
    }
}
