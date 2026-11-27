<?php
/**
 * Plugin Name: Login Locker
 * Description: Disable direct access to your sites /wp-login.php script, plus user notifications based on actions.
 * Author: Austin Passy
 * Author URI: https://austin.passy.co/
 * Version: 3.0.1
 * Requires at least: 6.8
 * Tested up to: 7.2.0
 * Requires PHP: 8.4
 * Plugin URI: https://github.com/thefrosty/wp-login-locker
 * GitHub Plugin URI: https://github.com/thefrosty/wp-login-locker
 * Primary Branch: develop
 * Release Asset: true
 */

namespace TheFrosty\WpLoginLocker;

defined('ABSPATH') || exit;

use Dwnload\WpSettingsApi\WpSettingsApi;
use TheFrosty\WpLoginLocker\Settings\Settings;
use TheFrosty\WpUtilities\Plugin\DefaultServiceProvider;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use TheFrosty\WpUtilities\WpAdmin\GitHubUpdateChecker;
use WP_CLI;
use function defined;
use function is_readable;
use function register_activation_hook;

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

$plugin = PluginFactory::create('login-locker');
$plugin->addServiceProvider(new DefaultServiceProvider());
$plugin
    ->add(new Actions\Login())
    ->add(new Actions\Logout())
    ->add(new Actions\NewUser())
    ->add(new DisablePluginUpdateCheck())
    ->add(new Login\WpLogin())
    ->add(new Settings())
    ->add(new WpCore\WpSignup())
    ->add(new WpSettingsApi(Settings::factory('3.0.1')))
    ->addOnHook(Admin\Menu::class, 'admin_init')
    ->addOnHook(GitHubUpdateChecker::class, 'plugins_loaded', admin_only: true, args: ['thefrosty/wp-login-locker'])
    ->addOnHook(Login\Login::class, 'login_init', 5)
    ->addOnHook(Login\LastLoginColumns::class, 'admin_init', 10, true)
    ->addOnHook(UserProfile\LastLogin::class, 'admin_init', 10, true)
    ->addOnHook(UserProfile\EmailNotificationSetting::class, 'admin_init', 10, true)
    ->initialize();

if (defined('WP_CLI') && WP_CLI) {
    $login_link_command = new Cli\LoginLinkCommand();
    $login_link_command->setPlugin($plugin);
    WP_CLI::add_command(Cli\LoginLinkCommand::COMMAND, $login_link_command);
}

require_once 'helpers.php';
register_activation_hook(__FILE__, static function (): void {
    new Login\WpLogin()->activate();
});
