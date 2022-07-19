<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Login;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpLoginLocker\LoginLocker;

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
        $this->assertCount(7, $constants);
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
        try {
            $user_id = self::factory()->user->create();
            \wp_set_current_user($user_id);
            $activate = $this->reflection->getMethod('activate');
            $activate->setAccessible(true);
            \do_action('activate_' . $this->wpLogin->getPlugin()->getFile(), $activate->invoke($this->wpLogin));
            $this->assertNull($activate->invoke($this->wpLogin));
            $actual = \get_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY, true);
            $this->assertNotEmpty($actual);
            \delete_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test loginAuthCheck(). With logout action
     */
    public function testLoginAuthCheckWithLogoutAction(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'loginAuthCheck'));
        try {
            $this->wpLogin->getRequest()->query->set('action', 'logout');
            $this->wpLogin->getRequest()->query->set('_wpnonce', \wp_create_nonce('log-out'));
            $loginAuthCheck = $this->reflection->getMethod('loginAuthCheck');
            $loginAuthCheck->setAccessible(true);
            $this->assertNull($loginAuthCheck->invoke($this->wpLogin));
            $this->assertEquals(0, \did_action('wp_verify_nonce_failed'));
            $this->wpLogin->getRequest()->query->remove('action');
            $this->wpLogin->getRequest()->query->remove('_wpnonce');
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test loginAuthCheck(). With auth check query key
     */
    public function testLoginAuthCheckWithAuthKey(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'loginAuthCheck'));
        try {
            $user = self::factory()->user->create_and_get();
            $this->wpLogin->getRequest()->query->set(WpLogin::AUTH_CHECK_KEY, $user->user_login);
            $loginAuthCheck = $this->reflection->getMethod('loginAuthCheck');
            $loginAuthCheck->setAccessible(true);
            $this->assertNull($loginAuthCheck->invoke($this->wpLogin));
            $this->wpLogin->getRequest()->query->remove(WpLogin::AUTH_CHECK_KEY);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test loginAuthCheck(). With saved cookie
     */
    public function testLoginAuthCheckWithCookie(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'loginAuthCheck'));
        try {
            $user = self::factory()->user->create_and_get();
            $cookieValue = $this->reflection->getMethod('getCookieValue');
            $cookieValue->setAccessible(true);
            $this->wpLogin->getRequest()->cookies->set(
                WpLogin::COOKIE_NAME,
                $cookieValue->invoke($this->wpLogin, $user->user_email)
            );
            $loginAuthCheck = $this->reflection->getMethod('loginAuthCheck');
            $loginAuthCheck->setAccessible(true);
            $this->assertNull($loginAuthCheck->invoke($this->wpLogin));
            \wp_update_user(['ID' => $user->ID, 'user_email' => 'anewemail@email.test']);
            $this->go_to(\wp_login_url());
            try {
                $loginAuthCheck->invoke($this->wpLogin);
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(\WPDieException::class, $exception);
                if (\ob_get_level()) {
                    \ob_get_clean();
                }
            }
            $this->wpLogin->getRequest()->cookies->remove(WpLogin::COOKIE_NAME);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test lostPasswordMessage().
     */
    public function testLostPasswordMessage(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'lostPasswordMessage'));
        try {
            $lostPasswordMessage = $this->reflection->getMethod('lostPasswordMessage');
            $lostPasswordMessage->setAccessible(true);
            $expected = 'This is a message';
            $actual = $lostPasswordMessage->invoke($this->wpLogin, $expected);
            $this->assertIsString($actual);
            $this->assertSame($expected, $actual);
            $_GET['action'] = 'lostpassword';
            $this->go_to(\add_query_arg('action', 'lostpassword', \wp_login_url()));
            $actual = $lostPasswordMessage->invoke($this->wpLogin, \wpautop($expected));
            $this->assertIsString($actual);
            $this->assertStringNotContainsString('class="message"', $actual);
            unset($_GET['action']);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test noAuthLoginHtml().
     */
    public function testNoAuthLoginHtml(): void
    {
        $this->assertTrue(\method_exists($this->wpLogin, 'noAuthLoginHtml'));
        try {
            $noAuthLoginHtml = $this->reflection->getMethod('noAuthLoginHtml');
            $noAuthLoginHtml->setAccessible(true);
            try {
                $noAuthLoginHtml->invoke($this->wpLogin);
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(\WPDieException::class, $exception);
                if (\ob_get_level()) {
                    \ob_get_clean();
                }
            }
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
