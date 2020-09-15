<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\WpCore;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\WpCore\WpSignup;

/**
 * Class WpSignupTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group wp-core
 */
class WpSignupTest extends TestCase
{

    /**
     * @var WpSignup $wpSignup
     */
    private $wpSignup;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->wpSignup = new WpSignup();
        $this->wpSignup->setPlugin($this->plugin);
        $this->wpSignup->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->wpSignup);
    }

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
        $this->assertTrue(\method_exists($this->wpSignup, 'addHooks'));
        $provider = $this->getMockProvider(WpSignup::class);
        $provider->expects($this->exactly(1))
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
        $this->assertTrue(\method_exists($this->wpSignup, 'redirectWpSignup'));
        try {
            $this->wpSignup->getRequest()->request->set('user_name', 'admin');
            $redirectWpSignup = $this->reflection->getMethod('redirectWpSignup');
            $redirectWpSignup->setAccessible(true);
            try {
                $redirectWpSignup->invoke($this->wpSignup);
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(\WPDieException::class, $exception);
            }
            $this->wpSignup->getRequest()->request->remove('user_name');
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
