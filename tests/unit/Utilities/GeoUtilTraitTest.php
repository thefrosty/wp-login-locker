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
        $server = $this->class->getRequest()->server;
        $server->set('REMOTE_ADDR', '127.0.0.1');
        $this->assertSame('127.0.0.1', $this->class->getIP());
        $server->remove('REMOTE_ADDR');
        $this->assertNull($this->class->getIP());
        $server->set('REMOTE_ADDR', 'not-an-ip');
        $this->assertNull($this->class->getIP());
        $server->remove('REMOTE_ADDR');
    }

    /**
     * Test getIP() ignores client-supplied headers unless the request comes from a trusted proxy.
     */
    public function testGetIPIgnoresSpoofedHeaders(): void
    {
        $server = $this->class->getRequest()->server;
        $server->set('REMOTE_ADDR', '203.0.113.10');
        $server->set('HTTP_CLIENT_IP', '198.51.100.1');
        $server->set('HTTP_X_FORWARDED_FOR', '198.51.100.2');
        $server->set('HTTP_CF_CONNECTING_IP', '198.51.100.3');
        $this->assertSame('203.0.113.10', $this->class->getIP());

        $proxies = static fn(): array => ['203.0.113.0/24'];
        \add_filter('thefrosty/wp_utilities/trusted_proxies', $proxies);
        $server->set('HTTP_X_FORWARDED_FOR', '198.51.100.9, 192.0.2.4');
        $this->assertSame('192.0.2.4', $this->class->getIP());
        $server->set('HTTP_X_FORWARDED_FOR', 'not-an-ip');
        $this->assertSame('203.0.113.10', $this->class->getIP());
        \remove_filter('thefrosty/wp_utilities/trusted_proxies', $proxies);

        $server->remove('REMOTE_ADDR');
        $server->remove('HTTP_CLIENT_IP');
        $server->remove('HTTP_X_FORWARDED_FOR');
        $server->remove('HTTP_CF_CONNECTING_IP');
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
