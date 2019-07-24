<?php
/**
 * Plugin Name: Login Locker
 * Description: Disable direct access to your sites /wp-login.php script, plus user notifications based on actions.
 * Author: Austin Passy
 * Author URI: https://github.com/thefrosty
 * Version: 1.1.1
 * Requires at least: 4.9
 * Tested up to: 5.1
 * Requires PHP: 7.2
 * Plugin URI: https://github.com/thefrosty/wp-login-locker
 */

namespace Dwnload\WpLoginLocker;

\defined('ABSPATH') || exit;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\PluginFactory;

if (\is_readable(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

$plugin = PluginFactory::create('login-locker');
$plugin->getContainer()[LoginLocker::CONTAINER_REQUEST] = static function () {
    return Request::createFromGlobals();
};

$plugin
    ->add(new Actions\Login())
    ->add(new Actions\NewUser())
    ->add(new Login\WpLogin())
    ->add(new WpCore\WpSignup())
    ->addOnHook(UserProfile\LastLogin::class, 'admin_init', 10, true)
    ->addOnHook(UserProfile\EmailNotificationSetting::class, 'admin_init', 10, true)
    ->addOnHook(Login\LastLoginColumns::class, 'admin_init', 10, true)
    ->initialize();

\register_activation_hook(__FILE__, static function () {
    (new Login\WpLogin())->activate();
});

\call_user_func_array(
    function ($filter) {
        \add_filter($filter, function ($value) use ($filter) {
            if (!empty($value->response) && \array_key_exists(\plugin_basename(__FILE__), $value->response)) {
                unset($value->response[\plugin_basename(__FILE__)]);
            }

            return $value;
        });
    },
    ['pre_site_transient_update_plugins', 'site_transient_update_plugins']
);
