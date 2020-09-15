<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Login;

use Dwnload\WpSettingsApi\Api\Options;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Login\Login;
use TheFrosty\WpLoginLocker\Settings\Settings;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 * @group login
 */
class LoginTest extends TestCase
{

    /**
     * @var Login $login
     */
    private $login;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->login = new Login();
        $this->reflection = $this->getReflection($this->login);
    }

    public function tearDown(): void
    {
        unset($this->login);
        parent::tearDown();
    }

    /**
     * Test new Login().
     */
    public function testConstructor(): void
    {
        try {
            $settings = $this->reflection->getProperty('settings');
            $settings->setAccessible(true);
            $actual = $settings->getValue($this->login);
            $this->assertIsArray($actual);
            $this->assertCount(0, $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->login, 'addHooks'));
        $provider = $this->getMockProvider(Login::class);
        $provider->expects($this->exactly(0))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var Login $provider */
        $provider->addHooks();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooksWithSettings(): void
    {
        $this->setUpSettingOptions();
        $this->assertTrue(\method_exists($this->login, 'addHooks'));
        $provider = $this->getMockProvider(Login::class);
        $provider->expects($this->exactly(3))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var Login $provider */
        $provider->addHooks();
    }

    /**
     * Test wpAddInlineLoginStyle().
     */
    public function testWpAddInlineLoginStyle(): void
    {
        $this->assertTrue(\method_exists($this->login, 'wpAddInlineLoginStyle'));
        try {
            $wpAddInlineLoginStyle = $this->reflection->getMethod('wpAddInlineLoginStyle');
            $wpAddInlineLoginStyle->setAccessible(true);
            $this->assertNull($wpAddInlineLoginStyle->invoke($this->login));
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test wpAddInlineLoginStyle().
     */
    public function testWpAddInlineLoginStyleWithFile(): void
    {
        try {
            $this->setUpSettingOptions();
            $login = new Login();
            \do_action('login_enqueue_scripts');
            $wpAddInlineLoginStyle = $this->getReflection($login)->getMethod('wpAddInlineLoginStyle');
            $wpAddInlineLoginStyle->setAccessible(true);
            $this->assertNull($wpAddInlineLoginStyle->invoke($login));
            // With attachment
            $filename = \dirname(ABSPATH) . '/tests/assets/300.jpg';
            $contents = \file_get_contents($filename);
            $upload = \wp_upload_bits(\wp_basename($filename), null, $contents);
            $this->assertTrue(empty($upload['error']));
            $id = $this->_make_attachment($upload);
            \update_option(Settings::LOGIN_SETTINGS, [
                Settings::LOGIN_SETTING_LOGO => \wp_get_attachment_url($id),
            ]);
            \ob_start();
            $wpAddInlineLoginStyle->invoke($login);
            $html = \ob_get_clean();
            $this->assertIsString($html);
            $this->setUpSettingOptions(true);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test loginHeaderUrl().
     */
    public function testLoginHeaderUrl(): void
    {
        $this->assertTrue(\method_exists($this->login, 'loginHeaderUrl'));
        try {
            $loginHeaderUrl = $this->reflection->getMethod('loginHeaderUrl');
            $loginHeaderUrl->setAccessible(true);
            $actual = $loginHeaderUrl->invoke($this->login, '');
            $this->assertIsString($actual);
            $this->assertNotSame('', $actual);
            $this->assertSame(\home_url(), $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test loginHeaderTitle().
     */
    public function testLoginHeaderTitle(): void
    {
        $this->assertTrue(\method_exists($this->login, 'loginHeaderTitle'));
        try {
            $loginHeaderTitle = $this->reflection->getMethod('loginHeaderTitle');
            $loginHeaderTitle->setAccessible(true);
            $actual = $loginHeaderTitle->invoke($this->login, '');
            $this->assertIsString($actual);
            $this->assertNotSame('', $actual);
            $this->assertSame(\get_bloginfo('description'), $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * @param bool $delete
     */
    protected function setUpSettingOptions(bool $delete = false): void
    {
        if (!$delete) {
            \update_option(Settings::LOGIN_SETTINGS, [
                Settings::LOGIN_SETTING_LOGO => 'https://via.placeholder.com/300.jpg',
            ]);
        } else {
            \delete_option(Settings::LOGIN_SETTINGS);
        }
    }
}
