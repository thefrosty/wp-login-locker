<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Actions;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\Login;
use TheFrosty\WpLoginLocker\LoginLocker;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 */
class LoginTest extends TestCase
{

    /**
     * @var Login $login
     */
    private $login;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->login = new Login();
        $this->login->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->login);
    }

    public function tearDown(): void
    {
        unset($this->login);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->login, 'addHooks'));
        $this->login->addHooks();
    }

    /**
     * Test wpLoginAction().
     */
    public function testWpLoginAction(): void
    {
        $this->assertTrue(\method_exists($this->login, 'wpLoginAction'));
        try {
            $wpLoginAction = $this->reflection->getMethod('wpLoginAction');
            $wpLoginAction->setAccessible(true);
            $WP_User = $this->getMockBuilder('WP_User')
                ->disableOriginalConstructor()
                ->getMock();
            $WP_User->ID = 0;
            \WP_Mock::passthruFunction('add_user_meta');
            \WP_Mock::passthruFunction('get_user_meta');
            \WP_Mock::passthruFunction('wp_schedule_single_event');
            $wpLoginAction->invoke($this->login, 'user_1', $WP_User);
            $this->assertTrue(\did_action(LoginLocker::HOOK_PREFIX . 'wp_login'));
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
