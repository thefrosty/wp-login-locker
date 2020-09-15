<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Utilities;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;

/**
 * Class GeoUtilTraitTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group utilities
 */
class GeoUtilTraitTest extends TestCase
{

    private $class;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->class = new class() implements HttpFoundationRequestInterface {
            use GeoUtilTrait, HttpFoundationRequestTrait;
        };
        $this->class->setRequest(Request::createFromGlobals());
    }

    public function tearDown(): void
    {
        unset($this->class);
        parent::tearDown();
    }

    /**
     * Test getIP().
     */
    public function testGetIP(): void
    {
        $HTTP_CLIENT_IP = '127.0.0.1';
        $HTTP_X_FORWARDED_FOR = '127.0.0.1';
        $this->assertIsString($this->class->getIP());
        $this->class->getRequest()->server->set('HTTP_CLIENT_IP', $HTTP_CLIENT_IP);
        $this->assertIsString($this->class->getIP());
        $this->class->getRequest()->server->remove('HTTP_CLIENT_IP');
        $this->class->getRequest()->server->set('HTTP_X_FORWARDED_FOR', $HTTP_X_FORWARDED_FOR);
        $this->assertIsString($this->class->getIP());
        $this->class->getRequest()->server->remove('HTTP_X_FORWARDED_FOR');
    }

    /**
     * Test getUserAgent().
     */
    public function testGetUserAgent(): void
    {
        $userAgent = $this->class->getUserAgent();
        $this->assertIsString($userAgent);
    }
}
