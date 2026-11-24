<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\UserProfile;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\UserProfile\LastLogin;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;

/**
 * Class LastLoginTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group user-profile
 */
#[CoversClass(LastLogin::class)]
#[CoversClass(NewUser::class)]
#[CoversTrait(GeoUtilTrait::class)]
#[Group('user-profile')]
class LastLoginTest extends TestCase
{

    private LastLogin $lastLogin;

    /**
     * Setup.
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->lastLogin = new LastLogin();
        $this->lastLogin->setPlugin($this->plugin);
        $this->lastLogin->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->lastLogin);
    }

    #[Override]
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
        }
    }

    /**
     * Test login times are shown in the site's timezone, not UTC.
     */
    public function testLoginTimesUseTheSiteTimezone(): void
    {
        $timezone = \get_option('timezone_string');
        \update_option('timezone_string', 'America/Los_Angeles');
        $user = self::factory()->user->create_and_get();
        $time = \time();
        \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY, $time);
        $format = \get_option('date_format') . '\\ ' . \get_option('time_format');
        $expected = \wp_date($format, $time);

        $this->assertSame($expected, $this->lastLogin->getCurrentLogin($user->ID));
        $this->assertSame($expected, $this->lastLogin->getLastLogin($user->ID));
        $this->assertNotSame(\gmdate(\get_option('time_format'), $time), \wp_date(\get_option('time_format'), $time));

        \update_option('timezone_string', $timezone);
        \wp_delete_user($user->ID);
    }

    /**
     * Test getCurrentLogin().
     */
    public function testGetCurrentLogin(): void
    {
        $this->assertTrue(\method_exists($this->lastLogin, 'getCurrentLogin'));
        try {
            $getCurrentLogin = $this->reflection->getMethod('getCurrentLogin');
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
        }
    }
}
