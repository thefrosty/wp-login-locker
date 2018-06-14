<?php
/**
 * Plugin Name: Login Locker
 * Description: .
 * Author: Austin Passy
 * Author URI: http://github.com/thefrosty
 * Version: 1.0.0
 * Requires at least: 4.9
 * Tested up to: 4.9
 * Requires PHP: 7.0
 * Plugin URI: https://github.com/dwnload/wp-login-locker
 */

defined('ABSPATH') || exit;

use Dwnload\WpLoginLocker\Actions\Login;
use Dwnload\WpLoginLocker\Actions\NewUser;
use Dwnload\WpLoginLocker\Login\LastLoginColumns;
use Dwnload\WpLoginLocker\Login\WpLogin;
use Dwnload\WpLoginLocker\LoginLocker;
use Dwnload\WpLoginLocker\WpCore\WpSignup;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\PluginFactory;

$login_locker = (new LoginLocker())->setRequest(Request::createFromGlobals());
PluginFactory::create('login-locker')
    ->add(new Login())
    ->add(new NewUser())
    ->add((new WpLogin())->setRequest($login_locker->getRequest()))
    ->add((new WpSignup())->setRequest($login_locker->getRequest()))
    ->addOnHook(LastLoginColumns::class, 'admin_init', 10, true)
    ->initialize();

call_user_func_array(
    function ($filter) {
        add_filter($filter, function ($value) use ($filter) {
            if (!empty($value->response) && array_key_exists(plugin_basename(__FILE__), $value->response)) {
                unset($value->response[plugin_basename(__FILE__)]);
            }

            return $value;
        });
    },
    ['pre_site_transient_update_plugins', 'site_transient_update_plugins']
);
