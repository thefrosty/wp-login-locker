<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Plugins;

use Dwnload\WpLoginLocker\Plugins\WpUserProfiles\UserEmailSection;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class PluginsLoader
 *
 * @package Dwnload\WpLoginLocker\Plugins
 */
class PluginsLoader implements WpHooksInterface
{
    public function addHooks()
    {
        if (class_exists(\WP_User_Profile_Section::class)) {
            (new UserEmailSection())->addHooks();
        }
    }
}
