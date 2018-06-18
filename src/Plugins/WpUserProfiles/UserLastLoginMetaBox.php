<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Plugins\WpUserProfiles;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class UserLastLoginMetaBox
 *
 * @package Dwnload\WpLoginLocker\Plugins\WpUserProfiles
 */
class UserLastLoginMetaBox implements PluginAwareInterface, WpHooksInterface
{
    use HooksTrait, PluginAwareTrait;

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction('wp_user_profiles_add_meta_boxes', [$this, 'addMetaBox'], 10, 2);
    }

    /**
     * @param string $hook
     * @param \WP_User|null $user
     */
    protected function addMetaBox($hook = '', \WP_User $user = null)
    {
        \add_meta_box(
            'wp-login-locker-last-login-ip',
            esc_attr_x('Login Status', 'users user-admin edit screen', 'wp-login-locker'),
            function(\WP_User $user = null) {
                $this->lastLoginMetaBoxCallback($user);
            },
            $hook,
            'side',
            'high',
            $user
        );
    }

    /**
     * @param \WP_User|null $user
     */
    private function lastLoginMetaBoxCallback(\WP_User $user = null)
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/metaboxes/user-profile-last-login.php';
        echo \ob_get_clean();
    }
}
