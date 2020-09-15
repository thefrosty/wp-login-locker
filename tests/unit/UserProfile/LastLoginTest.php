<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\UserProfile;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\UserProfile\LastLogin;

/**
 * Class LastLoginTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group user-profile
 */
class LastLoginTest extends TestCase
{

    /**
     * @var LastLogin $lastLogin
     */
    private $lastLogin;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->lastLogin = new LastLogin();
        $this->lastLogin->setPlugin($this->plugin);
        $this->lastLogin->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->lastLogin);
    }

    public function tearDown(): void
    {
        unset($this->lastLogin);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'addHooks'));
        $provider = $this->getMockProvider(LastLogin::class);
        $provider->expects($this->exactly(5))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var LastLogin $provider */
        $provider->addHooks();
    }

    /**
     * Test getLastLoginIp().
     */
    public function testGetLastLoginIp(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'getLastLoginIp'));
        try {
            $getLastLoginIp = $this->reflection->getMethod('getLastLoginIp');
            $getLastLoginIp->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            $actual = $getLastLoginIp->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            foreach (\range(1, 4) as $i) {
                \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY, \sprintf('10.0.0.%d', $i), false);
            }
            $actual = $getLastLoginIp->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            \delete_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY);
            $actual = $getLastLoginIp->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            $this->assertSame('No data', $actual);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test getCurrentLoginIp().
     */
    public function testGetCurrentLoginIp(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'getCurrentLoginIp'));
        try {
            $getCurrentLoginIp = $this->reflection->getMethod('getCurrentLoginIp');
            $getCurrentLoginIp->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            $actual = $getCurrentLoginIp->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            foreach (\range(1, 4) as $i) {
                \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY, \sprintf('10.0.0.%d', $i), false);
            }
            $actual = $getCurrentLoginIp->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            $this->assertSame('10.0.0.4', $actual);
            \delete_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY);
            $actual = $getCurrentLoginIp->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            $this->assertSame('No data', $actual);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test getLastLogin().
     */
    public function testGetLastLogin(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'getLastLogin'));
        try {
            $getLastLogin = $this->reflection->getMethod('getLastLogin');
            $getLastLogin->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            $actual = $getLastLogin->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            foreach (\range(1, 4) as $i) {
                \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY, \strtotime('-%s minutes', ++$i));
            }
            $actual = $getLastLogin->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            \delete_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY);
            $actual = $getLastLogin->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            $this->assertSame('No data', $actual);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test getCurrentLogin().
     */
    public function testGetCurrentLogin(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'getCurrentLogin'));
        try {
            $getCurrentLogin = $this->reflection->getMethod('getCurrentLogin');
            $getCurrentLogin->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            $actual = $getCurrentLogin->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            foreach (\range(1, 4) as $i) {
                \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY, \strtotime('-%s minutes', ++$i));
            }
            $actual = $getCurrentLogin->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            \delete_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY);
            $actual = $getCurrentLogin->invoke($this->lastLogin, $user->ID);
            $this->assertIsString($actual);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test showExtraUserFields().
     */
    public function testShowExtraUserFields(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'showExtraUserFields'));
        try {
            $showExtraUserFields = $this->reflection->getMethod('showExtraUserFields');
            $showExtraUserFields->setAccessible(true);
            \ob_start();
            $showExtraUserFields->invoke($this->lastLogin, null);
            $actual = \ob_get_clean();
            $this->assertEmpty($actual);
            $this->assertStringNotContainsString('Your recent login data', $actual);
            $user = self::factory()->user->create_and_get();
            \ob_start();
            $showExtraUserFields->invoke($this->lastLogin, $user);
            $actual = \ob_get_clean();
            $this->assertIsString($actual);
            $this->assertStringContainsString('Your recent login data', $actual);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
