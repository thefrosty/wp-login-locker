<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

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
}
