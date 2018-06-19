<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker;

use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class AbstractLoginLocker
 *
 * @package Dwnload\WpLoginLocker
 */
abstract class AbstractLoginLocker extends AbstractHookProvider implements PluginAwareInterface, RequestsInterface, WpHooksInterface
{
    use HooksTrait, PluginAwareTrait, RequestsTrait;
}
