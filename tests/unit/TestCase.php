<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionObject;
use TheFrosty\WpUtilities\Plugin\Container;
use TheFrosty\WpUtilities\Plugin\Plugin;
use TheFrosty\WpUtilities\Plugin\PluginFactory;

/**
 * Class TestCase
 * @package TheFrosty\Tests\WpLoginLocker
 */
class TestCase extends \WP_UnitTestCase
{

    public const string METHOD_ADD_FILTER = 'addFilter';

    /** @var Container $container */
    protected $container;

    protected Plugin $plugin;

    protected ReflectionObject $reflection;

    /**
     * @internal Workaround to allow the tests to run on PHPUnit 10.
     * @link https://core.trac.wordpress.org/ticket/59486
     */
    public function expectDeprecated(): void
    {
    }

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        // Set the filename to the root of the plugin (not the test plugin) (so we have asset access without mocks).
        $filename = \dirname(__DIR__, 2) . '/wp-login-locker.php';
        $this->plugin = PluginFactory::create('wp-login-locker', $filename);
        $this->container = $this->plugin->getContainer();
    }

    /**
     * Tear down.
     */
    public function tearDown(): void
    {
        unset($this->container, $this->plugin, $this->reflection);
        parent::tearDown();
    }

    /**
     * Gets an instance of the \ReflectionObject.
     * @param object $argument
     * @return ReflectionObject
     */
    protected function getReflection(object $argument): ReflectionObject
    {
        static $reflector;

        if (
            !isset($reflector[$argument::class]) ||
            !($reflector[$argument::class] instanceof ReflectionObject)
        ) {
            $reflector[$argument::class] = new ReflectionObject($argument);
        }

        return $reflector[$argument::class];
    }

    /**
     * Get a Mock Provider.
     * @param string $className
     * @return MockObject
     */
    protected function getMockProvider(string $className): MockObject
    {
        return $this->getMockBuilder($className)
            ->onlyMethods([self::METHOD_ADD_FILTER])
            ->getMock();
    }
}
