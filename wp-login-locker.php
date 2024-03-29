<?php
/**
 * Plugin Name: Login Locker
 * Description: Disable direct access to your sites /wp-login.php script, plus user notifications based on actions.
 * Author: Austin Passy
 * Author URI: https://github.com/thefrosty
 * Version: 2.4.0
 * Requires at least: 6.2
 * Tested up to: 6.5
 * Requires PHP: 8.1
 * Plugin URI: https://github.com/thefrosty/wp-login-locker
 * GitHub Plugin URI: https://github.com/thefrosty/wp-login-locker
 * Primary Branch: develop
 * Release Asset: true
 */

namespace TheFrosty\WpLoginLocker;

\defined('ABSPATH') || exit;

use Dwnload\WpSettingsApi\WpSettingsApi;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpLoginLocker\Settings\Settings;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;

if (\is_readable(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

$plugin = PluginFactory::create('login-locker');
$plugin->getContainer()[LoginLocker::CONTAINER_REQUEST] = static function (): Request {
    return Request::createFromGlobals();
};

$plugin
    ->add(new Actions\Login())
    ->add(new Actions\NewUser())
    ->add(new DisablePluginUpdateCheck())
    ->add(new Login\WpLogin())
    ->add(new Settings())
    ->add(new WpCore\WpSignup())
    ->add(new WpSettingsApi(Settings::factory('2.4.0')))
    ->addOnHook(Login\Login::class, 'login_init', 5)
    ->addOnHook(Login\LastLoginColumns::class, 'admin_init', 10, true)
    ->addOnHook(UserProfile\LastLogin::class, 'admin_init', 10, true)
    ->addOnHook(UserProfile\EmailNotificationSetting::class, 'admin_init', 10, true)
    ->initialize();

require_once 'helpers.php';
\register_activation_hook(__FILE__, static function () {
    (new Login\WpLogin())->activate();
});
