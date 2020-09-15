<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Settings;

use Dwnload\WpSettingsApi\Settings\FieldManager;
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
    }

    public function tearDown(): void
    {
        unset($this->settings);
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
    public function testInit(): void
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
}
