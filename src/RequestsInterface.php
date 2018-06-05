<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface RequestsInterface
 *
 * @package Dwnload\WpLoginLocker
 */
interface RequestsInterface
{
    /**
     * Set the Request.
     *
     * @param Request $request Main plugin instance.
     */
    public function setRequest(Request $request);

    /**
     * Get the Request.
     *
     * @return Request
     */
    public function getRequest(): Request;
}
