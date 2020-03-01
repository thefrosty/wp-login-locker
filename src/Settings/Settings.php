<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Settings;

use Dwnload\WpLoginLocker\AbstractLoginLocker;
use Dwnload\WpSettingsApi\Api\Script;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\Api\Style;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\FieldTypes;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use Dwnload\WpSettingsApi\WpSettingsApi;

/**
 * Class Settings
 * @package Dwnload\WpLoginLocker\Settings
 */
class Settings extends AbstractLoginLocker
{

    public const EMAIL_SETTINGS = self::PREFIX . 'email_settings';
    public const EMAIL_SETTING_PRETEXT = 'pretext';
    public const EMAIL_SETTING_MESSAGE = 'message';
    public const LOGIN_SETTINGS = self::PREFIX . 'login_settings';
    public const LOGIN_SETTING_LOGO = 'logo';
    private const PREFIX = 'login_locker_';
    private const DOMAIN = 'login-locker';
    private const MENU_SLUG = self::DOMAIN . '-settings';

    /**
     * Creat the PluginSettings object.
     * @return \Dwnload\WpSettingsApi\Api\PluginSettings
     */
    public static function factory(): \Dwnload\WpSettingsApi\Api\PluginSettings
    {
        return SettingsApiFactory::create([
            'domain' => self::DOMAIN,
            'file' => __FILE__, // Path to WpSettingsApi file (not required, see README for more info).
            'menu-slug' => self::MENU_SLUG,
            'menu-title' => 'Login Locker', // Title found in menu
            'page-title' => 'Login Locker Settings', // Title output at top of settings page
            'prefix' => self::PREFIX,
            'version' => '1.3.0',
        ]);
    }

    /**
     * Register our callback to the WP Settings API action hook
     * `WpSettingsApi::ACTION_PREFIX . 'init'`. This custom action passes three parameters (two prior to version 2.7)
     * so you have to register a priority and the parameter count.
     */
    public function addHooks()
    {
        $this->addAction(WpSettingsApi::ACTION_PREFIX . 'init', [$this, 'init'], 10, 3);
        $this->addFilter(WpSettingsApi::FILTER_PREFIX . 'admin_scripts', [$this, 'adminScripts']);
        $this->addFilter(WpSettingsApi::FILTER_PREFIX . 'admin_styles', [$this, 'adminStyles']);
    }

    /**
     * Initiate our setting to the Section & Field Manager classes.
     *
     * SettingField requires the following settings (passes as an array or set explicitly):
     * [
     *  SettingField::NAME
     *  SettingField::LABEL
     *  SettingField::DESC
     *  SettingField::TYPE
     *  SettingField::SECTION_ID
     * ]
     *
     * @param SectionManager $section_manager
     * @param FieldManager $field_manager
     * @param WpSettingsApi $wp_settings_api
     * @see SettingField for additional options for each field passed to the output
     *
     */
    protected function init(
        SectionManager $section_manager,
        FieldManager $field_manager,
        WpSettingsApi $wp_settings_api
    ): void {
        // Check if using more than once (slug set below in `SettingsApiFactory::create()`).
        if ($wp_settings_api->getPluginInfo()->getMenuSlug() !== self::MENU_SLUG) {
            return;
        }

        /**
         * Login Settings Section
         */
        $login_section_id = $section_manager->addSection(
            new SettingSection([
                SettingSection::SECTION_ID => self::LOGIN_SETTINGS, // Unique section ID
                SettingSection::SECTION_TITLE => 'Login Settings',
            ])
        );

        // Passing Field settings as an Array
        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::LOGIN_SETTING_LOGO,
                SettingField::LABEL => \esc_html__('Login Logo', 'wp-login-locker'),
                SettingField::DESC => \esc_html__(
                    'Logo to replace WordPress\' logo on the login page (must be uploaded to media library).',
                    'wp-login-locker'
                ),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_FILE,
                SettingField::SECTION_ID => $login_section_id,
            ])
        );

        /**
         * Email Settings Section
         */
        $email_section_id = $section_manager->addSection(
            new SettingSection([
                SettingSection::SECTION_ID => self::EMAIL_SETTINGS,
                SettingSection::SECTION_TITLE => 'Email Settings',
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::EMAIL_SETTING_PRETEXT,
                SettingField::LABEL => \esc_html__('Pre Text', 'wp-login-locker'),
                SettingField::DESC => '%1$s Site name',
                SettingField::TYPE => FieldTypes::FIELD_TYPE_TEXTAREA,
                SettingField::DEFAULT => $this->getSettingPretext(),
                SettingField::SANITIZE => '\wp_kses_post',
                SettingField::SECTION_ID => $email_section_id,
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::EMAIL_SETTING_MESSAGE,
                SettingField::LABEL => \esc_html__('Message', 'wp-login-locker'),
                SettingField::DESC => \esc_html__(
                    'Email body, use the keys listed below for text replacement.',
                    'wp-login-locker'
                ),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_TEXTAREA,
                SettingField::DEFAULT => $this->getSettingMessage(),
                SettingField::SANITIZE => '\wp_kses_post',
                SettingField::SECTION_ID => $email_section_id,
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => '',
                SettingField::LABEL => \esc_html__('Message', 'wp-login-locker'),
                SettingField::DEFAULT => '<ul>
<li><code>%1$s</code> User first and last name (display name)</li>
<li><code>%2$s</code> User agent</li>
<li><code>%3$s</code> IP address</li>
<li><code>%4$s</code> Login URL (with auth check and re-auth)</li>
<li><code>%5$s</code> Site name</li>
<li><code>%6$s</code> Site (admin) email</li>
</ul>',
                SettingField::TYPE => FieldTypes::FIELD_TYPE_HTML,
                SettingField::SECTION_ID => $email_section_id,
            ])
        );
    }

    /**
     * The default script needs to be moved from the vendor directory somewhere into our app since the
     * vendor directory is outside of the doc root.
     * @param Script[] $scripts
     * @return array
     */
    protected function adminScripts(array $scripts): array
    {
        $plugin = \dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor/dwnload/wp-settings-api/src/';
        \array_walk($scripts, function (Script $script, int $key) use (&$scripts, $plugin): void {
            if ($script->getHandle() === WpSettingsApi::ADMIN_SCRIPT_HANDLE) {
                $scripts[$key]->setSrc(\plugins_url('src/assets/js/admin.js', $plugin));
            } elseif ($script->getHandle() === WpSettingsApi::ADMIN_MEDIA_HANDLE) {
                $scripts[$key]->setSrc(\plugins_url('src/assets/js/wp-media-uploader.js', $plugin));
            }
        });

        return $scripts;
    }

    /**
     * The default style needs to be moved from the vendor directory somewhere into our app since the
     * vendor directory is outside of the doc root.
     * @param Style[] $styles
     * @return array
     */
    protected function adminStyles(array $styles): array
    {
        $plugin = \dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor/dwnload/wp-settings-api/src/';
        \array_walk($styles, function (Style $style, int $key) use (&$styles, $plugin): void {
            if ($style->getHandle() === WpSettingsApi::ADMIN_STYLE_HANDLE) {
                $styles[$key]->setSrc(\plugins_url('src/assets/css/admin.css', $plugin));
            }
        });

        return $styles;
    }

    /**
     * Get the default pretext setting.
     * @return string
     */
    private function getSettingPretext(): string
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-pretext.php';
        $content = \ob_get_clean();

        return \strval($content);
    }

    /**
     * Get the default message setting.
     * @return string
     */
    private function getSettingMessage(): string
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-notice.php';
        $content = \ob_get_clean();

        return \strval($content);
    }
}
