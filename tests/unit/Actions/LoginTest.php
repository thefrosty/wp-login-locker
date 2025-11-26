<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Actions;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\Login;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\WpMail\WpMail;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 */
#[CoversClass(Login::class)]
#[Group('actions')]
class LoginTest extends TestCase
{

    private Login $login;

    /**
     * Setup.
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->login = new Login();
        $this->login->setPlugin($this->plugin);
        $this->login->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->login);
        $wp_mail = $this->reflection->getProperty('wp_mail');
        $wp_mail->setValue($this->login, new WpMail());
    }

    #[Override]
    public function tearDown(): void
    {
        unset($this->login);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->login, 'addHooks'));
        $provider = $this->getMockProvider(Login::class);
        $provider->expects($this->exactly(4))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var Login $provider */
        $provider->addHooks();
    }

    /**
     * Test wpLoginAction().
     */
    public function testWpLoginAction(): void
    {
        $this->assertTrue(\method_exists($this->login, 'wpLoginAction'));
        try {
            $wpLoginAction = $this->reflection->getMethod('wpLoginAction');
            $WP_User = new \WP_User();
            $wpLoginAction->invoke($this->login, $WP_User->user_login, $WP_User);
            $this->assertEquals(1, \did_action(LoginLocker::HOOK_PREFIX . 'wp_login'));
            $this->assertNull($this->reflection->getDefaultProperties()['wp_mail']);

            // Test with a valid user and user meta
            $WP_User = self::factory()->user->create_and_get();
            \add_user_meta($WP_User->ID, LoginLocker::LAST_LOGIN_IP_META_KEY, '192.168.1.256');
            $wpLoginAction->invoke($this->login, $WP_User->user_login, $WP_User);
            $this->assertEquals(2, \did_action(LoginLocker::HOOK_PREFIX . 'wp_login'));
            \delete_user_meta($WP_User->ID, LoginLocker::LAST_LOGIN_IP_META_KEY);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test sendTestEmail().
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendTestEmail(): void
    {
        $this->assertTrue(\method_exists($this->login, 'sendTestEmail'));
        $user = self::factory()->user->create();
        \wp_set_current_user($user);
        \set_current_screen('dashboard');
        $this->assertTrue(\is_admin());
        $query = $this->login->getRequest()->query;
        $nonce = \wp_create_nonce(Login::ADMIN_ACTION_SEND_EMAIL);
        $query->set('action', Login::ADMIN_ACTION_SEND_EMAIL);
        $query->set(Login::ADMIN_ACTION_NONCE, $nonce);
        $_GET['action'] = Login::ADMIN_ACTION_SEND_EMAIL;
        $_GET[Login::ADMIN_ACTION_NONCE] = $nonce;
        $this->assertNotSame(0, $user);
        $this->assertEquals($nonce, $query->get(Login::ADMIN_ACTION_NONCE));
        $this->assertIsInt(\wp_verify_nonce($nonce, Login::ADMIN_ACTION_SEND_EMAIL));
    }

    /**
     * Test sendTestEmail(). No user or nonce.
     */
    public function testSendTestEmailNoUserOrNonce(): void
    {
        $this->assertTrue(\method_exists($this->login, 'sendTestEmail'));
        try {
            $sendTestEmail = $this->reflection->getMethod('sendTestEmail');
            try {
                $sendTestEmail->invoke($this->login);
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(\WPDieException::class, $exception);
            }
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test postMetaCleanup().
     */
    public function testPostMetaCleanup(): void
    {
        $this->assertTrue(\method_exists($this->login, 'postMetaCleanup'));
        try {
            $postMetaCleanup = $this->reflection->getMethod('postMetaCleanup');
            $WP_User = new \WP_User();
            $this->assertNull($postMetaCleanup->invoke($this->login, $WP_User->ID));
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test setProtectedMeta().
     */
    public function testSetProtectedMeta(): void
    {
        $this->assertTrue(\method_exists($this->login, 'setProtectedMeta'));
        try {
            $setProtectedMeta = $this->reflection->getMethod('setProtectedMeta');
            $actual = $setProtectedMeta->invoke($this->login, false, 'bad_key');
            $this->assertFalse($actual);
            foreach ([LoginLocker::LAST_LOGIN_IP_META_KEY, LoginLocker::LAST_LOGIN_TIME_META_KEY] as $key) {
                $actual = $setProtectedMeta->invoke($this->login, false, $key);
                $this->assertTrue($actual);
            }
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test getEmailPretext().
     */
    public function testGetEmailPretext(): void
    {
        $this->assertTrue(\method_exists($this->login, 'getEmailPretext'));
        try {
            $wp_mail = $this->reflection->getProperty('wp_mail');
            $wp_mail->setValue($this->login, new WpMail());
            $getEmailPretext = $this->reflection->getMethod('getEmailPretext');
            $actual = $getEmailPretext->invoke($this->login);
            $this->assertIsString($actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test getEmailMessage().
     */
    public function testGetEmailMessage(): void
    {
        $this->assertTrue(\method_exists($this->login, 'getEmailMessage'));
        try {
            $getEmailMessage = $this->reflection->getMethod('getEmailMessage');
            $user = self::factory()->user->create_and_get();
            $actual = $getEmailMessage->invoke($this->login, $user);
            $this->assertIsString($actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test getUserName().
     */
    public function testGetUserName(): void
    {
        $this->assertTrue(\method_exists($this->login, 'getUserName'));
        try {
            $getUserName = $this->reflection->getMethod('getUserName');
            foreach (
                [
                    'first_name' => 'First Name',
                    'display_name' => 'Mr. First Name',
                    'user_login' => 'mr_first',
                ] as $key => $val
            ) {
                $user = self::factory()->user->create_and_get([$key => $val]);
                $actual = $getUserName->invoke($this->login, $user);
                $this->assertContains(
                    $actual,
                    [$user->first_name, $user->display_name, $user->user_login]
                );
            }
            $user = self::factory()->user->create_and_get();
            $user->first_name = '';
            $user->display_name = '';
            $actual = $getUserName->invoke($this->login, $user);
            $this->assertSame($user->user_login, $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }

    /**
     * Test getHomeUrl().
     */
    public function testGetHomeUrl(): void
    {
        $this->assertTrue(\method_exists($this->login, 'getHomeUrl'));
        try {
            $getHomeUrl = $this->reflection->getMethod('getHomeUrl');
            $actual = $getHomeUrl->invoke($this->login);
            $this->assertIsString($actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }
}
