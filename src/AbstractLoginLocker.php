<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker;

use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;

/**
 * Class AbstractLoginLocker
 * @package TheFrosty\WpLoginLocker
 */
abstract class AbstractLoginLocker extends AbstractHookProvider implements
    HttpFoundationRequestInterface
{

    use HttpFoundationRequestTrait;
}
