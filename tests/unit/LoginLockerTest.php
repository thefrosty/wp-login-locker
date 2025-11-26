<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker;

use PHPUnit\Framework\Attributes\CoversClass;
use TheFrosty\WpLoginLocker\LoginLocker;
use function array_values;

/**
 * Class LoginLockerTest
 * @package TheFrosty\Tests\WpLoginLocker
 */
#[CoversClass(LoginLocker::class)]
class LoginLockerTest extends TestCase
{

    private LoginLocker $login_locker;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        $this->login_locker = new class implements LoginLocker {
        };
    }

    public function tearDown(): void
    {
        unset($this->login_locker);
    }

    /**
     * Test class has constants.
     */
    public function testConstants(): void
    {
        $expected = [
            LoginLocker::HOOK_PREFIX,
            LoginLocker::META_PREFIX,
            LoginLocker::LAST_LOGIN,
            LoginLocker::LAST_LOGIN_IP_META_KEY,
            LoginLocker::LAST_LOGIN_TIME_META_KEY,
            LoginLocker::USER_EMAIL,
            LoginLocker::USER_EMAIL_META_KEY,
            LoginLocker::CONTAINER_REQUEST,
            LoginLocker::WP_LOGIN,
            LoginLocker::CONTAINER_GITHUB_ARGS,
        ];
        $constants = $this->getReflection($this->login_locker)->getConstants();
        $this->assertNotEmpty($constants);
        $this->assertSame($expected, array_values($constants));
    }
}
