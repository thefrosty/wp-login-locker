<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Utilities;

use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use function explode;
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
     * @return string
     */
    public function getIP(): string
    {
        $ip = explode(',', getIpAddress($this->getRequest()) ?? '0.0.0.0');

        return sanitize_text_field(end($ip));
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
