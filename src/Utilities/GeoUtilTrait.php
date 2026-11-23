<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Utilities;

use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use function TheFrosty\WpUtilities\getIpAddress;

/**
 * Trait GeoUtilTrait
 * @package TheFrosty\WpLoginLocker\Utilities
 */
trait GeoUtilTrait
{

    use HttpFoundationRequestTrait;

    /**
     * Get IP Address of user.
     * Uses `REMOTE_ADDR`, which the client can't spoof (client headers like `X-Forwarded-For` or `Client-IP`
     * would let an attacker match the victim's last login IP and suppress the new login notification).
     * Sites behind a reverse proxy or CDN can trust it via the wp-utilities filters, e.g.:
     * `add_filter('thefrosty/wp_utilities/trusted_proxies', fn() => ['10.0.0.0/8']);`
     * `add_filter('thefrosty/wp_utilities/trusted_proxy_header', fn() => 'HTTP_CF_CONNECTING_IP');`
     * @return string|null The IP address, or null when it can't be determined.
     */
    public function getIP(): ?string
    {
        return getIpAddress($this->getRequest());
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
