<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Utilities;

use Override;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;

/**
 * Class GeoUtilTraitTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 */
#[CoversTrait(GeoUtilTrait::class)]
#[Group('utilities')]
class GeoUtilTraitTest extends TestCase
{

    private $class;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->class = new class() implements HttpFoundationRequestInterface {
            use GeoUtilTrait, HttpFoundationRequestTrait;
        };
        $this->class->setRequest(Request::createFromGlobals());
    }

    #[Override]
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
     * Test getIP() ignores client-supplied headers unless they're explicitly trusted.
     */
    public function testGetIPIgnoresSpoofedHeaders(): void
    {
        $server = $this->class->getRequest()->server;
        $server->set('REMOTE_ADDR', '203.0.113.10');
        $server->set('HTTP_CLIENT_IP', '198.51.100.1');
        $server->set('HTTP_X_FORWARDED_FOR', '198.51.100.2');
        $this->assertSame('203.0.113.10', $this->class->getIP());

        $filter = static fn(): array => ['HTTP_X_FORWARDED_FOR'];
        \add_filter('login_locker/trusted_ip_headers', $filter);
        $server->set('HTTP_X_FORWARDED_FOR', '198.51.100.9, 192.0.2.4');
        $this->assertSame('192.0.2.4', $this->class->getIP());
        $server->set('HTTP_X_FORWARDED_FOR', 'not-an-ip');
        $this->assertSame('203.0.113.10', $this->class->getIP());
        \remove_filter('login_locker/trusted_ip_headers', $filter);

        $server->remove('HTTP_CLIENT_IP');
        $server->remove('HTTP_X_FORWARDED_FOR');
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
