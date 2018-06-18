<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\UserProfile;

use Dwnload\WpLoginLocker\RequestsInterface;
use Dwnload\WpLoginLocker\RequestsTrait;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class UserProfile
 *
 * @package Dwnload\WpLoginLocker\UserProfile
 */
abstract class UserProfile implements PluginAwareInterface, RequestsInterface, WpHooksInterface
{
    use HooksTrait, PluginAwareTrait, RequestsTrait;

    /**
     * User meta fields to save.
     *
     * @var array $fields
     */
    protected $fields = [];

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction('personal_options_update', [$this, 'saveExtraProfileFields']);
        $this->addAction('edit_user_profile_update', [$this, 'saveExtraProfileFields']);
    }

    /**
     * @param int $user_id The current users ID.
     */
    function saveExtraProfileFields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
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
}
