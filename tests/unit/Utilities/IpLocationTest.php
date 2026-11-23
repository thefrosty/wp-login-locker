<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Utilities;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Settings\Settings;
use TheFrosty\WpLoginLocker\Utilities\IpLocation;
use WP_Error;
use function json_encode;

/**
 * Class IpLocationTest
 * @package TheFrosty\Tests\WpLoginLocker\Utilities
 */
#[CoversClass(IpLocation::class)]
#[Group('utilities')]
class IpLocationTest extends TestCase
{

    private const string IP = '1.1.1.1';

    private IpLocation $ipLocation;

    /** @var array<int, array{url: string, args: array}> */
    private array $requests = [];

    /** @var array|WP_Error The mocked HTTP response. */
    private array|WP_Error $response;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->ipLocation = new IpLocation();
        $this->requests = [];
        $this->response = $this->jsonResponse([
            'ipAddress' => self::IP,
            'cityName' => 'Sydney',
            'regionName' => 'New South Wales',
            'countryName' => 'Australia',
            'countryCode' => 'AU',
            'isProxy' => false,
        ]);
        \update_option(Settings::GENERAL_SETTINGS, [Settings::GENERAL_SETTING_IP_LOCATION => 'on']);
        \add_filter('pre_http_request', [$this, 'mockHttp'], 10, 3);
    }

    #[Override]
    public function tearDown(): void
    {
        \remove_filter('pre_http_request', [$this, 'mockHttp']);
        \delete_option(Settings::GENERAL_SETTINGS);
        \delete_transient(IpLocation::TRANSIENT_COOLDOWN);
        \delete_transient(IpLocation::TRANSIENT_REQUEST_LOG);
        \delete_transient(IpLocation::TRANSIENT_CACHE_PREFIX . \md5(self::IP));
        \remove_all_filters(IpLocation::FILTER_PREFIX . 'api_key');
        \remove_all_filters(IpLocation::FILTER_PREFIX . 'rate_limits');
        unset($this->ipLocation);
        parent::tearDown();
    }

    /**
     * Intercept HTTP requests.
     * @param mixed $preempt
     * @param array $args
     * @param string $url
     * @return array|WP_Error
     */
    public function mockHttp(mixed $preempt, array $args, string $url): array|WP_Error
    {
        $this->requests[] = ['url' => $url, 'args' => $args];

        return $this->response;
    }

    public function testDisabledByDefault(): void
    {
        \delete_option(Settings::GENERAL_SETTINGS);
        $this->assertFalse(IpLocation::isEnabled());
        $this->assertNull($this->ipLocation->getLocationString(self::IP));
        $this->assertCount(0, $this->requests);
    }

    public function testGetLocationStringUsesFreeApi(): void
    {
        $this->assertSame('Sydney, New South Wales, Australia', $this->ipLocation->getLocationString(self::IP));
        $this->assertCount(1, $this->requests);
        $this->assertSame('https://free.freeipapi.com/api/v1/json/1.1.1.1', $this->requests[0]['url']);
        $this->assertArrayNotHasKey('Authorization', $this->requests[0]['args']['headers']);
        $this->assertNull($this->ipLocation->getLocationString(null));
    }

    public function testProxyIsFlagged(): void
    {
        $this->response = $this->jsonResponse(['countryName' => 'Germany', 'isProxy' => true]);
        $this->assertSame('Germany (proxy/VPN)', $this->ipLocation->getLocationString(self::IP));
    }

    public function testResultsAreCached(): void
    {
        $this->ipLocation->lookup(self::IP);
        $this->ipLocation->lookup(self::IP);
        $this->ipLocation->getLocationString(self::IP);
        $this->assertCount(1, $this->requests);
    }

    public function testNotFoundIsCached(): void
    {
        $this->response = $this->jsonResponse(['countryName' => '']);
        $this->assertNull($this->ipLocation->lookup(self::IP));
        $this->assertNull($this->ipLocation->lookup(self::IP));
        $this->assertCount(1, $this->requests);
    }

    public function testPrivateAndReservedIpsAreSkipped(): void
    {
        foreach (['10.0.0.1', '192.168.1.1', '127.0.0.1', '::1', 'not-an-ip'] as $ip) {
            $this->assertNull($this->ipLocation->lookup($ip));
        }
        $this->assertCount(0, $this->requests);
    }

    public function testRateLimitIsNeverExceeded(): void
    {
        // Distinct public IPs so the per-IP cache doesn't absorb the requests.
        for ($i = 1; $i <= 20; $i++) {
            $ip = "8.8.4.{$i}";
            $this->ipLocation->lookup($ip);
            \delete_transient(IpLocation::TRANSIENT_CACHE_PREFIX . \md5($ip));
        }
        $this->assertCount(IpLocation::FREE_RATE_LIMITS[10], $this->requests);
        $this->assertLessThan(10, \count($this->requests), 'Free API: 10 requests per 10 seconds');
    }

    public function testRateLimitsCanBeFiltered(): void
    {
        \add_filter(IpLocation::FILTER_PREFIX . 'rate_limits', static fn(): array => [60 => 2]);
        foreach (['8.8.8.1', '8.8.8.2', '8.8.8.3'] as $ip) {
            $this->ipLocation->lookup($ip);
            \delete_transient(IpLocation::TRANSIENT_CACHE_PREFIX . \md5($ip));
        }
        $this->assertCount(2, $this->requests);
    }

    public function testTooManyRequestsStartsCooldown(): void
    {
        $this->response = [
            'headers' => ['retry-after' => '120'],
            'body' => '',
            'response' => ['code' => 429, 'message' => 'Too Many Requests'],
            'cookies' => [],
            'filename' => null,
        ];
        $this->assertNull($this->ipLocation->lookup(self::IP));
        $this->assertEquals(429, \get_transient(IpLocation::TRANSIENT_COOLDOWN));

        // No more requests during the cooldown, even for other IPs.
        $this->assertNull($this->ipLocation->lookup('9.9.9.9'));
        $this->assertCount(1, $this->requests);
    }

    public function testConnectionErrorStartsCooldown(): void
    {
        $this->response = new WP_Error('http_request_failed', 'timeout');
        $this->assertNull($this->ipLocation->lookup(self::IP));
        $this->assertNotFalse(\get_transient(IpLocation::TRANSIENT_COOLDOWN));
    }

    public function testPaidApiKey(): void
    {
        \add_filter(IpLocation::FILTER_PREFIX . 'api_key', static fn(): string => 'secret');
        for ($i = 1; $i <= 12; $i++) {
            $ip = "8.8.4.{$i}";
            $this->ipLocation->lookup($ip);
            \delete_transient(IpLocation::TRANSIENT_CACHE_PREFIX . \md5($ip));
        }
        // Paid plans aren't rate limited.
        $this->assertCount(12, $this->requests);
        $this->assertSame('https://us.freeipapi.com/api/json/8.8.4.1', $this->requests[0]['url']);
        $this->assertSame('Bearer secret', $this->requests[0]['args']['headers']['Authorization']);
    }

    /**
     * @param array $body
     * @return array
     */
    private function jsonResponse(array $body): array
    {
        return [
            'headers' => [],
            'body' => (string)json_encode($body),
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies' => [],
            'filename' => null,
        ];
    }
}
