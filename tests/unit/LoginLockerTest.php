<?php declare(strict_types=1);

namespace Dwnload\WpRestApi\Tests;

use Dwnload\WpLoginLocker\LoginLocker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TestWpRestApiCache
 * @package Dwnload\WpRestApi\Tests
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
        $this->login_locker = new LoginLocker(Request::createFromGlobals());
    }

    public function tearDown()
    {
        unset($this->login_locker);
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
