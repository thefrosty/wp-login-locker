<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Login;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Settings\Settings;

/**
 * Class WpLoginTest
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 * @group login
 */
class WpLoginTest extends TestCase
{

    /**
     * @var WpLogin $wpLogin
     */
    private $wpLogin;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->wpLogin = new WpLogin();
        $this->wpLogin->setPlugin($this->plugin);
        $this->wpLogin->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->wpLogin);
    }

    public function tearDown(): void
    {
        unset($this->wpLogin);
        parent::tearDown();
    }

    /**
     * Test new Login().
     */
    public function testConstructor(): void
    {
        $constants = $this->reflection->getConstants();
        $this->assertIsArray($constants);
        $this->assertCount(6, $constants);
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'addHooks'));
        $provider = $this->getMockProvider(WpLogin::class);
        $provider->expects($this->exactly(2))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var WpLogin $provider */
        $provider->addHooks();
    }

    /**
     * Test activate().
     */
    public function testActivateNoUser(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'activate'));
        try {
            $activate = $this->reflection->getMethod('activate');
            $activate->setAccessible(true);
            \do_action('activate_' . $this->wpLogin->getPlugin()->getFile(), $activate->invoke($this->wpLogin));
            $this->assertNull($activate->invoke($this->wpLogin));
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test activate().
     */
    public function testActivate(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'activate'));
        /**
         * Need to test against headers already sent.
         */
//        try {
//            $user_id = self::factory()->user->create();
//            \wp_set_current_user($user_id);
//            $activate = $this->reflection->getMethod('activate');
//            $activate->setAccessible(true);
//            \do_action('activate_' . $this->wpLogin->getPlugin()->getFile(), $activate->invoke($this->wpLogin));
//            $this->assertNull($activate->invoke($this->wpLogin));
//            $actual = \get_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY, true);
//            $this->assertNotEmpty($actual);
//            \delete_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY);
//        } catch (\ReflectionException $exception) {
//            $this->assertInstanceOf(\ReflectionException::class, $exception);
//            $this->markAsRisky();
//        }
    }

    /**
     * Test loginAuthCheck().
     */
    public function testLoginAuthCheck(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'loginAuthCheck'));
        /**
         * Need to fix terminate() -> exit().
         */
//        try {
//            \do_action('login_init');
//            $loginAuthCheck = $this->reflection->getMethod('loginAuthCheck');
//            $loginAuthCheck->setAccessible(true);
//            $this->assertNull($loginAuthCheck->invoke($this->wpLogin));
//        } catch (\ReflectionException $exception) {
//            $this->assertInstanceOf(\ReflectionException::class, $exception);
//            $this->markAsRisky();
//        }
    }
}
