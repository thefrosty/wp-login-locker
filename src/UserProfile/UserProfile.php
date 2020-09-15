<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\UserProfile;

use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpUtilities\Plugin\ContainerAwareTrait;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;

/**
 * Class UserProfile
 *
 * @package TheFrosty\WpLoginLocker\UserProfile
 */
abstract class UserProfile extends AbstractLoginLocker
{
    use ContainerAwareTrait, HooksTrait, PluginAwareTrait;

    public const USER_PROFILE_ID = 'login-locker-settings';
    public const USER_PROFILE_HOOK = LoginLocker::HOOK_PREFIX . 'user_profile/extra_fields';

    /**
     * User meta fields to save.
     *
     * @var array $fields
     */
    protected $fields = [];

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('show_user_profile', [$this, 'doUserProfileAction'], 19);
        $this->addAction('edit_user_profile', [$this, 'doUserProfileAction'], 19);
        $this->addAction('personal_options_update', [$this, 'saveExtraProfileFields']);
        $this->addAction('edit_user_profile_update', [$this, 'saveExtraProfileFields']);
    }

    /**
     * Add our custom hook.
     * @param \WP_User|null $user
     * @return void
     */
    protected function doUserProfileAction(\WP_User $user = null): void
    {
        if (!\did_action(self::USER_PROFILE_HOOK)) {
            \printf(
                '<h2 id="%s">%s</h2>',
                \esc_attr(self::USER_PROFILE_ID),
                \esc_html__('Login Locker Settings', 'wp-login-locker')
            );
            \do_action(self::USER_PROFILE_HOOK, $user);
        }
    }

    /**
     * If the inherited class set's fields, save them. Set to current users who can `read` meaning log in
     * to the admin.
     * @param int $user_id The current users ID.
     */
    protected function saveExtraProfileFields($user_id): void
    {
        if (empty($this->fields) || !\current_user_can('read')) {
            return;
        }

        foreach ($this->fields as $field) {
            if ($this->getRequest()->request->has($field)) {
                \update_user_meta($user_id, $field, $this->getRequest()->request->get($field));
            } else {
                \delete_user_meta($user_id, $field);
            }
        }
    }

    /**
     * Helper to get the user meta as an array.
     * @param int $user_id
     * @param string $key
     * @return array
     */
    protected function getUserMeta(int $user_id, string $key): array
    {
        return (array)\get_user_meta($user_id, $key, false);
    }
}
