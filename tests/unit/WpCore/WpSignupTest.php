<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\WpCore;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\WpCore\WpSignup;
use TheFrosty\WpUtilities\Exceptions\TerminationException;
use Throwable;
use WPDieException;
use function method_exists;

/**
 * Class WpSignupTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 */
#[CoversClass(WpSignup::class)]
#[Group('wp-core')]
class WpSignupTest extends TestCase
{

    private WpSignup $wpSignup;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->wpSignup = new WpSignup();
        $this->wpSignup->setPlugin($this->plugin);
        $this->wpSignup->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->wpSignup);
    }

    #[Override]
    public function tearDown(): void
    {
        unset($this->wpSignup);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(method_exists($this->wpSignup, 'addHooks'));
        $provider = $this->getMockProvider(WpSignup::class);
        $provider->expects($this->once())
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var WpSignup $provider */
        $provider->addHooks();
    }

    /**
     * Test redirectWpSignup().
     */
    public function testRedirectWpSignup(): void
    {
        $this->assertTrue(method_exists($this->wpSignup, 'redirectWpSignup'));
        try {
            $this->wpSignup->getRequest()->request->set('user_name', 'admin');
            $redirectWpSignup = $this->reflection->getMethod('redirectWpSignup');
            try {
                $redirectWpSignup->invoke($this->wpSignup);
            } catch (Throwable $exception) {
                $this->assertInstanceOf(WPDieException::class, $exception);
            }
            $this->wpSignup->getRequest()->request->remove('user_name');
            $this->expectException(TerminationException::class);
            $redirectWpSignup->invoke($this->wpSignup);
        } catch (ReflectionException) {
        }
    }
}
