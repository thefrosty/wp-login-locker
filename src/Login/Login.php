<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Login;

use Dwnload\WpSettingsApi\Api\Options;
use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Settings\Settings;
use function array_key_exists;
use function get_bloginfo;
use function home_url;
use function is_multisite;
use function sprintf;
use function wp_add_inline_style;
use function wp_make_link_relative;

/**
 * Class Login
 * @package TheFrosty\WpLoginLocker\Login
 */
class Login extends AbstractLoginLocker
{
    /**
     * Settings array.
     * @var array $settings
     */
    private mixed $settings;

    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->settings = Options::getOptions(Settings::LOGIN_SETTINGS);
    }

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        if (empty($this->settings)) {
            return;
        }
        $this->addAction('login_enqueue_scripts', [$this, 'wpAddInlineLoginStyle']);
        $this->addFilter('login_headerurl', [$this, 'loginHeaderUrl']);
        $this->addFilter('login_headertext', [$this, 'loginHeaderTitle']);
    }

    /**
     * Add our style inline of the wp-login page.
     */
    protected function wpAddInlineLoginStyle(): void
    {
        $logo = Options::getOption(Settings::LOGIN_SETTING_LOGO, Settings::LOGIN_SETTINGS, '');
        if (!array_key_exists(Settings::LOGIN_SETTING_LOGO, $this->settings) || empty($logo)) {
            return;
        }
        $login_h1 = version_compare($GLOBALS['wp_version'], '6.7', '>=') ? '.login .wp-login-logo' : '.login h1';
        $css = sprintf(
            '%s a {
	background-image: none, url(%s);
	background-size: contain;
}',
            $login_h1,
            wp_make_link_relative($logo),
        );
        wp_add_inline_style('login', $css);
    }

    /**
     * Replace the default link with the site's home URL.
     * @param string $url
     * @return string
     */
    protected function loginHeaderUrl(string $url): string
    {
        if (!is_multisite()) {
            return home_url();
        }

        return $url;
    }

    /**
     * Replace the default title with the site's description.
     * @param string $title
     * @return string
     */
    protected function loginHeaderTitle(string $title): string
    {
        if (!is_multisite()) {
            return get_bloginfo('description');
        }

        return $title;
    }
}
