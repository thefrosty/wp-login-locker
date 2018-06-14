<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait RequestsTrait
 *
 * @package Dwnload\WpLoginLocker
 */
trait RequestsTrait
{
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @var Request $request
     */
    private static $request;

    /**
     * {@inheritdoc}
     */
    public function setRequest(Request $request)
    {
        if (!(self::$request instanceof Request)) {
            self::$request = $request;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest(): Request
    {
        return self::$request;
    }
}
