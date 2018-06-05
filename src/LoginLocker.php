<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class LoginLocker
 *
 * @package Dwnload\WpLoginLocker
 */
final class LoginLocker implements RequestsInterface
{
    use RequestsTrait;

    const HOOK_PREFIX = 'login_locker/';
    const META_PREFIX = 'login_locker_';

    /**
     * LoginLocker constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->setRequest($request);
    }
}
