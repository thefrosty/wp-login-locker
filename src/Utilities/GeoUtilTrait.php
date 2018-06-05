<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\Utilities;

use Dwnload\WpLoginLocker\RequestsTrait;

/**
 * Class GeoUtil
 * @package Dwnload\WpLoginLocker\Utilities
 */
trait GeoUtilTrait
{
    use RequestsTrait;

    /**
     * Get IP Address of user.w
     *
     * @return string
     */
    public function getIP(): string
    {
        if ($this->getRequest()->server->has('HTTP_CLIENT_IP') &&
            !empty($this->getRequest()->server->get('HTTP_CLIENT_IP'))
        ) {
            $ip_address = $this->getRequest()->server->get('HTTP_CLIENT_IP');
        } elseif ($this->getRequest()->server->has('HTTP_X_FORWARDED_FOR') &&
            !empty($this->getRequest()->server->get('HTTP_X_FORWARDED_FOR'))
        ) {
            $ip_address = $this->getRequest()->server->get('HTTP_X_FORWARDED_FOR');
        } else {
            $ip_address = $this->getRequest()->server->get('REMOTE_ADDR');
        }

        return $ip_address;
    }

    /**
     * Get browser name.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->getRequest()->server->get('HTTP_USER_AGENT', 'Unknown');
    }
}
