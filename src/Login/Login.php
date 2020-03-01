<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Login;

use Dwnload\WpLoginLocker\AbstractLoginLocker;
use Dwnload\WpLoginLocker\Settings\Settings;
use Dwnload\WpSettingsApi\Api\Options;

/**
 * Class Login
 *
 * @package BeachbodyOnDemand\WpLogin
 */
class Login extends AbstractLoginLocker
{
    /**
     * Settings array.
     * @var array $settings
     */
    private $settings = [];

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
        $hook = \version_compare($GLOBALS['wp_version'], '5.2', '>=') ? 'login_headertext' : 'login_headertitle';
        $this->addFilter($hook, [$this, 'loginHeaderTitle']);
    }

    /**
     * Add our style inline of the wp-login page.
     */
    protected function wpAddInlineLoginStyle(): void
    {
        $logo = Options::getOption(Settings::LOGIN_SETTING_LOGO, Settings::LOGIN_SETTINGS, '');
        if (!\array_key_exists(Settings::LOGIN_SETTING_LOGO, $this->settings) || empty($logo)) {
            return;
        }
        $attachment = \wp_get_attachment_metadata(\attachment_url_to_postid($logo));
        if (empty($attachment) ||
            !\array_key_exists('width', $attachment) ||
            !\array_key_exists('height', $attachment)
        ) {
            return;
        }
        $css = \sprintf(
            '.login h1 a {
	background-image: none, url(%s);
	background-size: contain;
	width: %spx;
	height: %spx;
}',
            \wp_make_link_relative($logo),
            $attachment['width'],
            $attachment['height']
        );
        \wp_add_inline_style('login', $css);
    }

    /**
     * Replace the default link with the site's home URL.
     * @param string $url
     * @return string
     */
    protected function loginHeaderUrl(string $url): string
    {
        if (!\is_multisite()) {
            return \home_url();
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
        if (!\is_multisite()) {
            return \get_bloginfo('description');
        }

        return $title;
    }
}