<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Settings;

use Dwnload\WpSettingsApi\Api\Script;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\Api\Style;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\FieldTypes;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use Dwnload\WpSettingsApi\WpSettingsApi;
use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Actions\Login;
use TheFrosty\WpLoginLocker\UserProfile\UserProfile;

/**
 * Class Settings
 * @package TheFrosty\WpLoginLocker\Settings
 */
class Settings extends AbstractLoginLocker
{

    public const EMAIL_SETTINGS = self::PREFIX . 'email_settings';
    public const EMAIL_SETTING_PRETEXT = 'pretext';
    public const EMAIL_SETTING_MESSAGE = 'message';
    public const EMAIL_SETTING_BACKGROUND_COLOR = 'background_color';
    public const EMAIL_SETTING_FULL_BLEED_COLOR = 'full_bleed';
    public const EMAIL_SETTING_HEADER_IMAGE = 'email_header';
    public const EMAIL_SETTING_HERO_IMAGE = 'hero_image';
    public const BACKGROUND_COLOR_DEFAULT = '#222222';
    public const FULL_BLEED_COLOR_DEFAULT = '#709f2b';
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
            'version' => '2.0.0',
        ]);
    }

    /**
     * Register our callback to the WP Settings API action hook
     * `WpSettingsApi::ACTION_PREFIX . 'init'`. This custom action passes three parameters (two prior to version 2.7)
     * so you have to register a priority and the parameter count.
     */
    public function addHooks(): void
    {
        $this->addAction(WpSettingsApi::ACTION_PREFIX . 'init', [$this, 'init'], 10, 3);
        $this->addAction(WpSettingsApi::ACTION_PREFIX . 'settings_sidebars', [$this, 'sidebar'], 200);
        $this->addFilter(WpSettingsApi::FILTER_PREFIX . 'admin_scripts', [$this, 'adminScripts']);
        $this->addFilter(WpSettingsApi::FILTER_PREFIX . 'admin_styles', [$this, 'adminStyles']);
        $this->addFilter('plugin_action_links_' . $this->getPlugin()->getBasename(), [$this, 'addSettingsLink']);
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

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::EMAIL_SETTING_BACKGROUND_COLOR,
                SettingField::LABEL => \esc_html__('Background Color', 'wp-login-locker'),
                SettingField::DESC => \esc_html__('Email background color.', 'wp-login-locker'),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_COLOR,
                SettingField::DEFAULT => self::BACKGROUND_COLOR_DEFAULT,
                SettingField::SANITIZE => '\sanitize_hex_color',
                SettingField::SECTION_ID => $email_section_id,
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::EMAIL_SETTING_FULL_BLEED_COLOR,
                SettingField::LABEL => \esc_html__('Full Bleed Color', 'wp-login-locker'),
                SettingField::DESC => \esc_html__('Full bleed background section color.', 'wp-login-locker'),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_COLOR,
                SettingField::DEFAULT => self::FULL_BLEED_COLOR_DEFAULT,
                SettingField::SANITIZE => '\sanitize_hex_color',
                SettingField::SECTION_ID => $email_section_id,
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::EMAIL_SETTING_HEADER_IMAGE,
                SettingField::LABEL => \esc_html__('Email Header', 'wp-login-locker'),
                SettingField::DESC => \esc_html__('Email Header Image, suggested size 200x50.', 'wp-login-locker'),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_IMAGE,
                SettingField::SECTION_ID => $email_section_id,
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::EMAIL_SETTING_HERO_IMAGE,
                SettingField::LABEL => \esc_html__('Hero Image', 'wp-login-locker'),
                SettingField::DESC => \esc_html__(
                    'Hero Image Image (flush), suggested size 1200x600.',
                    'wp-login-locker'
                ),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_IMAGE,
                SettingField::SECTION_ID => $email_section_id,
            ])
        );
    }

    /**
     * Add a sidebar element.
     */
    protected function sidebar(): void
    {
        $query = $this->getRequest()->query;
        if ($query->has('sent') && \filter_var($query->get('sent'), \FILTER_VALIDATE_BOOLEAN)) {
            \printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                \esc_html__('Success - test email sent.', 'wp-login-locker')
            );
        }
        \printf(
            '<p><a href="%1$s" class="button button-secondary" onclick="return confirm(\'%3$s\');">%2$s</a></p>',
            \esc_url(
                \wp_nonce_url(
                    \add_query_arg(
                        'action',
                        Login::ADMIN_ACTION_SEND_EMAIL,
                        \admin_url('admin-post.php')
                    ),
                    Login::ADMIN_ACTION_SEND_EMAIL,
                    Login::ADMIN_ACTION_NONCE
                )
            ),
            \esc_html__('Send Test Email', 'wp-login-locker'),
            \esc_attr__('Send Test Email?', 'wp-login-locker')
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
     * Add settings page link to the plugins page.
     * @param array $actions
     * @return array
     */
    protected function addSettingsLink(array $actions): array
    {
        \array_unshift(
            $actions,
            \sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                \menu_page_url(self::MENU_SLUG, false),
                \esc_attr__('Settings for Login Locker', 'wp-login-locker'),
                \esc_html__('Settings', 'default')
            ),
            \sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                \admin_url(\sprintf('profile.php#%s', UserProfile::USER_PROFILE_ID)),
                \esc_attr__('Login Locker user email notifications settings', 'wp-login-locker'),
                \esc_html__('Emails', 'default')
            )
        );

        return $actions;
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
