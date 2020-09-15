<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Settings;

use Dwnload\WpSettingsApi\Api\Script;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\FieldTypes;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\WpSettingsApi;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Settings\Settings;
use TheFrosty\WpLoginLocker\UserProfile\LastLogin;

/**
 * Class SettingsTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group settings
 */
class SettingsTest extends TestCase
{

    /**
     * @var Settings $settings
     */
    private $settings;

    /** @var \WP_User $user */
    private $user;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->settings = new Settings();
        $this->settings->setPlugin($this->plugin);
        $this->settings->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->settings);
        $this->user = self::factory()->user->create_and_get(['role' => 'administrator']);
        \wp_set_current_user($this->user->ID);
    }

    public function tearDown(): void
    {
        unset($this->settings, $this->user);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->settings, 'addHooks'));
        $provider = $this->getMockBuilder(Settings::class)
            ->setMethods([self::METHOD_ADD_FILTER, 'getPlugin'])
            ->getMock();;
        $provider->expects($this->once())
            ->method('getPlugin')
            ->willReturn($this->plugin);
        $provider->expects($this->exactly(4))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var LastLogin $provider */
        $provider->addHooks();
    }

    /**
     * Test init().
     */
    public function testInitMock(): void
    {
        $this->assertTrue(\method_exists($this->settings, 'init'));
        try {
            $init = $this->reflection->getMethod('init');
            $init->setAccessible(true);
            $WpSettingsApi = $this->getMockBuilder(WpSettingsApi::class)
                ->setConstructorArgs([Settings::factory()])
                ->getMock();
            $SectionManager = $this->getMockBuilder(SectionManager::class)
                ->setConstructorArgs([$WpSettingsApi])
                ->getMock();
            $FieldManager = $this->getMockBuilder(FieldManager::class)->getMock();
            $this->assertNull($init->invoke($this->settings, $SectionManager, $FieldManager, $WpSettingsApi));
            $this->go_to(\menu_page_url($this->reflection->getConstant('MENU_SLUG')));
            $init->invoke($this->settings, $SectionManager, $FieldManager, $WpSettingsApi);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test init().
     */
    public function testInit(): void
    {
        try {
            \set_current_screen('dashboard');
            $this->assertTrue(\is_admin());
            $init = $this->reflection->getMethod('init');
            $init->setAccessible(true);
            $WpSettingsApi = new WpSettingsApi(Settings::factory());
            $init->invoke($this->settings, new SectionManager($WpSettingsApi), new FieldManager(), $WpSettingsApi);
            $getFields = FieldManager::getFields();
            $this->assertArrayHasKey(Settings::LOGIN_SETTINGS, $getFields);
            $this->assertCount(2, (array)$getFields[Settings::LOGIN_SETTINGS]);
            /** @var SettingField $setting_field */
            foreach ($getFields[Settings::LOGIN_SETTINGS] as $setting_field) {
                $this->assertInstanceOf(SettingField::class, $setting_field);
                switch ($setting_field->getName()) {
                    case Settings::LOGIN_SETTING_LOGO:
                        $this->assertSame($setting_field->getType(), FieldTypes::FIELD_TYPE_FILE);
                        $this->assertNull($setting_field->getSanitizeCallback());
                        break;
                }
            }
            unset($getFields);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test adminScripts().
     */
    public function testAdminScripts(): void
    {
        try {
            \set_current_screen('dashboard');
            $this->assertTrue(\is_admin());
            $adminScripts = $this->reflection->getMethod('adminScripts');
            $adminScripts->setAccessible(true);
            \do_action('admin_enqueue_scripts');
            $scripts = \apply_filters(WpSettingsApi::FILTER_PREFIX . 'admin_scripts', []);
            $adminScripts->invoke($this->settings, $scripts);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test adminStyles().
     */
    public function testAdminStyles(): void
    {
        try {
            \set_current_screen('dashboard');
            $this->assertTrue(\is_admin());
            $adminStyles = $this->reflection->getMethod('adminStyles');
            $adminStyles->setAccessible(true);
            \do_action('admin_enqueue_scripts');
            $scripts = \apply_filters(WpSettingsApi::FILTER_PREFIX . 'admin_styles', []);
            $adminStyles->invoke($this->settings, $scripts);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test addSettingsLink().
     */
    public function testAddSettingsLink(): void
    {
        try {
            \set_current_screen('plugins.php');
            $this->assertTrue(\is_admin());
            $addSettingsLink = $this->reflection->getMethod('addSettingsLink');
            $addSettingsLink->setAccessible(true);
            $actual = $addSettingsLink->invoke($this->settings, []);
            $this->assertIsArray($actual);
            $this->assertCount(2, $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
