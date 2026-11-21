<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Utilities;

use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use function apply_filters;
use function array_map;
use function end;
use function explode;
use function filter_var;
use function is_string;
use function sanitize_text_field;
use const FILTER_VALIDATE_IP;

/**
 * Trait GeoUtilTrait
 * @package TheFrosty\WpLoginLocker\Utilities
 */
trait GeoUtilTrait
{

    use HttpFoundationRequestTrait;

    /**
     * Get IP Address of user.
     * Only `REMOTE_ADDR` is trusted by default; client headers like `X-Forwarded-For` or `Client-IP`
     * can be set by anyone and would let an attacker spoof their IP (e.g. to match the victim's last
     * login IP and suppress the new login notification). Sites behind a reverse proxy or CDN can opt in:
     * `add_filter('login_locker/trusted_ip_headers', fn() => ['HTTP_CF_CONNECTING_IP']);`
     * @return string
     */
    public function getIP(): string
    {
        $server = $this->getRequest()->server;
        $ip = $server->get('REMOTE_ADDR');

        /**
         * Filter the $_SERVER keys (in priority order) trusted to contain the client IP.
         * Only add headers your proxy/CDN overwrites on every request.
         * @param string[] $headers Default empty array.
         */
        $headers = (array)apply_filters(LoginLocker::HOOK_PREFIX . 'trusted_ip_headers', []);
        foreach ($headers as $header) {
            $value = is_string($header) ? $server->get($header) : null;
            if (!is_string($value) || $value === '') {
                continue;
            }
            // The right-most entry is the one appended by the closest (trusted) proxy.
            $candidates = array_map('trim', explode(',', $value));
            $candidate = end($candidates);
            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                $ip = $candidate;
                break;
            }
        }

        if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            $ip = '0.0.0.0';
        }

        return sanitize_text_field($ip);
    }

    /**
     * Get browser name.
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->getRequest()->server->get('HTTP_USER_AGENT', 'Unknown');
    }
}
