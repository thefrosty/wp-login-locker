<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

use Pimple\Container;
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
    public function setRequest(Request $request = null)
    {
        if (!(self::$request instanceof Request)) {
            self::$request = $request ?? $this->getRequestFrom();
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

    /**
     * Get the Request from the container or recreate it.
     * @return Request
     */
    private function getRequestFrom(): Request
    {
        $container = $this->getPlugin()->getContainer();
        if ($container instanceof Container) {
            $request = $container->get(LoginLocker::CONTAINER_REQUEST);
        }

        return isset($request) && $request instanceof Request ? $request : Request::createFromGlobals();
    }
}
