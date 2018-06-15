<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Plugins\WpUserProfiles;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class UserEmailSection
 * @package Dwnload\WpLoginLocker\Plugins\WpUserProfiles
 */
class UserEmailSection extends \WP_User_Profile_Section implements PluginAwareInterface, WpHooksInterface
{
    use HooksTrait, PluginAwareTrait;

    const ARGS = [
        'id' => 'emails',
        'slug' => 'emails',
        'name' => 'Emails',
        'cap' => 'edit_profile',
        'icon' => 'dashicons-email-alt',
        'order' => 95,
    ];
    const USER_META_KEY = 'dwnload_disable_login_notification';

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
    }

    /**
     * Add the meta boxes for this section.
     *
     * @param  string $type
     * @param  null|\WP_User $user
     */
    protected function addMetaBoxes($type = '', $user = null)
    {
        // User Emails
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
     *
     * @return \WP_User|null|int|\WP_Error
     */
    public function save($user = null)
    {
        if (isset($_POST[self::USER_META_KEY])) {
            \update_user_meta($user->ID, self::USER_META_KEY, true);
        } else {
            \delete_user_meta($user->ID, self::USER_META_KEY);
        }
        return parent::save($user);
    }
}
