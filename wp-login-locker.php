<?php
/**
 * Plugin Name: Login Locker
 * Description: Disable direct access to your sites /wp-login.php script, plus user notifications based on actions.
 * Author: Austin Passy
 * Author URI: https://github.com/thefrosty
 * Version: 1.2.0
 * Requires at least: 5.0
 * Tested up to: 5.3
 * Requires PHP: 7.2
 * Plugin URI: https://github.com/thefrosty/wp-login-locker
 */

namespace Dwnload\WpLoginLocker;

\defined('ABSPATH') || exit;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;

if (\is_readable(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

$plugin = PluginFactory::create('login-locker');
$plugin->getContainer()[LoginLocker::CONTAINER_REQUEST] = static function () {
    return Request::createFromGlobals();
};

$plugin
    ->add(new DisablePluginUpdateCheck())
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
