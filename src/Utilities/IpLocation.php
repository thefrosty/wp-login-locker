<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Utilities;

use Dwnload\WpSettingsApi\Api\Options;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Settings\Settings;
use WP_Error;
use WP_Http;
use function __;
use function apply_filters;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function filter_var;
use function get_transient;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function max;
use function md5;
use function microtime;
use function rawurlencode;
use function sanitize_text_field;
use function set_transient;
use function sprintf;
use function trailingslashit;
use function trim;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_header;
use function wp_remote_retrieve_response_code;
use function wp_safe_remote_get;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_BOOL;
use const FILTER_VALIDATE_IP;
use const HOUR_IN_SECONDS;
use const MINUTE_IN_SECONDS;
use const WEEK_IN_SECONDS;

/**
 * Class IpLocation
 * Approximate IP geolocation via freeipapi.com, with a per-IP transient cache and a global
 * transient-backed rate limiter, so the free API's limits are never exceeded.
 * @link https://freeipapi.com/docs/api-reference/get-ip-info
 * @link https://freeipapi.com/rate-limit Free: 10 requests per 10 seconds, up to 60 per minute, per server IP.
 * @package TheFrosty\WpLoginLocker\Utilities
 */
class IpLocation
{

    public const string FREE_BASE_URL = 'https://free.freeipapi.com/api/v1/';
    public const string PAID_BASE_URL = 'https://us.freeipapi.com/api/';
    public const string JSON_ENDPOINT = 'json/';
    public const string SUBSCRIPTION_URL = 'https://freeipapi.com/docs/app/subscription';
    public const string FILTER_PREFIX = LoginLocker::HOOK_PREFIX . 'ip_location/';
    public const string FILTER_CACHE_TTL = self::FILTER_PREFIX . 'cache_ttl';
    public const string FILTER_COOLDOWN = self::FILTER_PREFIX . 'cooldown';
    public const string FILTER_NOT_FOUND_TTL = self::FILTER_PREFIX . 'not_found_ttl';
    public const string FILTER_PAID_BASE_URL = self::FILTER_PREFIX . 'paid_base_url';
    public const string FILTER_RATE_LIMITS = self::FILTER_PREFIX . 'rate_limits';
    public const string TRANSIENT_CACHE_PREFIX = LoginLocker::META_PREFIX . 'ipl_';
    public const string TRANSIENT_COOLDOWN = LoginLocker::META_PREFIX . 'ipl_cooldown';
    public const string TRANSIENT_REQUEST_LOG = LoginLocker::META_PREFIX . 'ipl_requests';

    /**
     * Half the free API's limits ({window seconds} => {max requests}), leaving headroom for concurrent
     * requests racing on the (non-atomic) transient and for other sites sharing the server's outgoing IP.
     */
    public const array FREE_RATE_LIMITS = [10 => 5, MINUTE_IN_SECONDS => 30];

    private const int TIMEOUT = 3;
    private const string NOT_FOUND = 'not_found';

    /**
     * Is the "Look up login location?" setting enabled?
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return filter_var(
            Options::getOption(Settings::FREE_IP_API_SETTING_IP_LOCATION, Settings::GENERAL_SETTINGS),
            FILTER_VALIDATE_BOOL
        );
    }

    /**
     * Get a human-readable location, e.g. "Sydney, New South Wales, Australia".
     * @param string|null $ip
     * @return string|null Null when disabled, unknown, or the lookup isn't possible right now.
     */
    public function getLocationString(?string $ip): ?string
    {
        if ($ip === null || !self::isEnabled()) {
            return null;
        }

        $location = $this->lookup($ip);
        if ($location === null) {
            return null;
        }

        $parts = array_values(
            array_filter(
                [$location['city'], $location['region'], $location['country']],
                static fn(string $part): bool => $part !== ''
            )
        );
        if ($parts === []) {
            return null;
        }

        $string = implode(', ', $parts);
        if ($location['is_proxy']) {
            $string = sprintf(__('%s (proxy/VPN)', 'wp-login-locker'), $string);
        }

        return $string;
    }

    /**
     * Look up an IP address. Results (including "not found") are cached in a transient.
     * @param string $ip
     * @return array{city: string, region: string, country: string, country_code: string, is_proxy: bool}|null
     */
    public function lookup(string $ip): ?array
    {
        // Private/reserved addresses can't be geolocated; don't spend a request on them.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        $cache_key = self::TRANSIENT_CACHE_PREFIX . md5($ip);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }
        if ($cached === self::NOT_FOUND || !$this->acquireRequestSlot()) {
            return null;
        }

        $location = $this->request($ip);
        if ($location === null) {
            set_transient($cache_key, self::NOT_FOUND, $this->filterInt(self::FILTER_NOT_FOUND_TTL, HOUR_IN_SECONDS));

            return null;
        }

        set_transient($cache_key, $location, $this->filterInt(self::FILTER_CACHE_TTL, WEEK_IN_SECONDS));

        return $location;
    }

    /**
     * Rate limiter: record a request in the sliding window log if every window has room.
     * Returns false (no request allowed) during a cooldown or when any window is full.
     * @return bool
     */
    protected function acquireRequestSlot(): bool
    {
        if (get_transient(self::TRANSIENT_COOLDOWN) !== false) {
            return false;
        }

        /**
         * Filter the rate limits, as {window seconds} => {max requests}. Return an empty array to disable.
         * Defaults to half the free limits; no limits when an API key is set (paid plans aren't rate limited).
         * @param array<int, int> $limits
         */
        $limits = (array)apply_filters(
            self::FILTER_RATE_LIMITS,
            $this->getApiKey() === null ? self::FREE_RATE_LIMITS : []
        );
        if ($limits === []) {
            return true;
        }

        $now = microtime(true);
        $longest = max(1, ...array_map('intval', array_keys($limits)));
        $log = get_transient(self::TRANSIENT_REQUEST_LOG);
        $log = array_values(
            array_filter(is_array($log) ? $log : [], static fn($time) => is_numeric($time) && $time > $now - $longest)
        );

        foreach ($limits as $window => $max) {
            $in_window = count(array_filter($log, static fn($time) => $time > $now - (int)$window));
            if ($in_window >= (int)$max) {
                return false;
            }
        }

        $log[] = $now;
        set_transient(self::TRANSIENT_REQUEST_LOG, $log, $longest);

        return true;
    }

    /**
     * Make the API request.
     * @param string $ip
     * @return array{city: string, region: string, country: string, country_code: string, is_proxy: bool}|null
     */
    protected function request(string $ip): ?array
    {
        $api_key = $this->getApiKey();
        $headers = ['Accept' => 'application/json'];
        if ($api_key !== null) {
            $headers['Authorization'] = sprintf('Bearer %s', $api_key);
            $base = (string)apply_filters(self::FILTER_PAID_BASE_URL, self::PAID_BASE_URL);
        }

        $response = wp_safe_remote_get(
            trailingslashit($base ?? self::FREE_BASE_URL) . self::JSON_ENDPOINT . rawurlencode($ip),
            ['timeout' => self::TIMEOUT, 'headers' => $headers]
        );

        $code = (int)wp_remote_retrieve_response_code($response);
        if ($code !== WP_Http::OK) {
            $this->startCooldown($response, $code);

            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return null;
        }

        $location = [
            'city' => $this->sanitize($data['cityName'] ?? ''),
            'region' => $this->sanitize($data['regionName'] ?? ''),
            'country' => $this->sanitize($data['countryName'] ?? ''),
            'country_code' => $this->sanitize($data['countryCode'] ?? ''),
            'is_proxy' => ($data['isProxy'] ?? false) === true,
        ];

        // Treat a response without a country as "not found".
        return $location['country'] === '' ? null : $location;
    }

    /**
     * Stop all requests for a while after a rate limit (429), server error or connection failure.
     * Honors a numeric `Retry-After` header.
     * @param array|WP_Error $response
     * @param int $code
     */
    private function startCooldown(array|WP_Error $response, int $code): void
    {
        // A 4xx other than 429 (e.g. a bad paid API key) doesn't mean the API is unavailable.
        if (
            $code >= WP_Http::BAD_REQUEST &&
            $code < WP_Http::INTERNAL_SERVER_ERROR &&
            $code !== WP_Http::TOO_MANY_REQUESTS
        ) {
            return;
        }

        $retry_after = $response instanceof WP_Error ? '' : wp_remote_retrieve_header($response, 'retry-after');
        $seconds = max(
            $this->filterInt(self::FILTER_COOLDOWN, MINUTE_IN_SECONDS),
            is_numeric($retry_after) ? (int)$retry_after : 0
        );
        set_transient(self::TRANSIENT_COOLDOWN, $code, $seconds);
    }

    /**
     * Get the paid plan API key from the "FreeIPAPI Key" setting.
     * @return string|null
     */
    private function getApiKey(): ?string
    {
        $key = Options::getOption(Settings::FREE_IP_API_SETTING_API_KEY, Settings::GENERAL_SETTINGS, null);
        $key = is_string($key) ? trim($key) : '';

        return $key !== '' ? $key : null;
    }

    /**
     * @param string $name The filter name.
     * @param int $default
     * @return int
     */
    private function filterInt(string $name, int $default): int
    {
        return max(1, (int)apply_filters($name, $default));
    }

    /**
     * Sanitize an API string value.
     * @param mixed $value
     * @return string
     */
    private function sanitize(mixed $value): string
    {
        return is_string($value) ? sanitize_text_field($value) : '';
    }
}
