<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker;

use TheFrosty\WpLoginLocker\LoginLocker;
use PHPUnit\Framework\TestCase;

/**
 * Class TestWpRestApiCache
 * @package TheFrosty\Tests\WpLoginLocker
 */
class LoginLockerTest extends TestCase
{

    /**
     * @var LoginLocker $login_locker
     */
    private $login_locker;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->login_locker = new LoginLocker();
    }

    public function tearDown()
    {
        unset($this->login_locker);
    }

    /**
     * Test class has constants.
     */
    public function testConstants()
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
        ];
        $constants = $this->getReflection()->getConstants();
        $this->assertNotEmpty($constants);
        $this->assertSame($expected, \array_values($constants));
    }

    /**
     * Gets an instance of the \ReflectionObject.
     *
     * @return \ReflectionObject
     */
    private function getReflection(): \ReflectionObject
    {
        static $reflector;

        if (!($reflector instanceof \ReflectionObject)) {
            $reflector = new \ReflectionObject($this->login_locker);
        }

        return $reflector;
    }
}
