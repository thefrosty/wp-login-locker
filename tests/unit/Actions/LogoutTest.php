<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Actions;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\Login;
use TheFrosty\WpLoginLocker\Actions\Logout;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpUtilities\Exceptions\TerminationException;
use function is_admin;
use function method_exists;
use function set_current_screen;
use function sprintf;
use function wp_create_nonce;
use function wp_set_current_user;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 */
#[CoversClass(Logout::class)]
#[Group('actions')]
#[UsesClass(Login::class)]
#[UsesClass(NewUser::class)]
#[UsesClass(WpLogin::class)]
class LogoutTest extends TestCase
{

    private Logout $logout;

    /**
     * Setup.
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->logout = new Logout();
        $this->logout->setPlugin($this->plugin);
        $this->logout->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->logout);
    }

    #[Override]
    public function tearDown(): void
    {
        unset($this->logout);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(method_exists($this->logout, 'addHooks'));
        $provider = $this->getMockProvider(Logout::class);
        $provider->expects($this->exactly(2))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var Logout $provider */
        $provider->addHooks();
    }

    /**
     * Test maybeLogout().
     */
    public function testMaybeLogoutNoRequest(): void
    {
        $this->assertTrue(method_exists($this->logout, 'maybeLogout'));
        $wpLoginAction = $this->reflection->getMethod('maybeLogout');
        $this->assertNull($wpLoginAction->invoke($this->logout));
    }

    /**
     * Test maybeLogout().
     */
    public function testMaybeLogout(): void
    {
        $this->assertTrue(method_exists($this->logout, 'maybeLogout'));
        $user = self::factory()->user->create_and_get();
        wp_set_current_user($user);
        set_current_screen('dashboard');
        $this->assertTrue(is_admin());
        $query = $this->logout->getRequest()->query;
        $nonce = wp_create_nonce(sprintf(Logout::ACTION_S, $user->ID));
        $query->set(Login::ADMIN_ACTION_NONCE, $nonce);
        $query->set('user_id', $user->ID);
        $this->expectException(TerminationException::class);
        $wpLoginAction = $this->reflection->getMethod('maybeLogout');
        $this->assertNull($wpLoginAction->invoke($this->logout));
    }

    /**
     * Test message().
     */
    public function testMessageDefault(): void
    {
        $this->assertTrue(method_exists($this->logout, 'message'));
        $message = $this->reflection->getMethod('message');
        $expected = 'This is a message to you.';
        $this->assertSame($expected, $message->invoke($this->logout, $expected));
    }

    /**
     * Test message().
     */
    public function testMessageChanged(): void
    {
        $this->assertTrue(method_exists($this->logout, 'message'));
        $query = $this->logout->getRequest()->query;
        $query->set(Logout::ACTION, true);
        $message = $this->reflection->getMethod('message');
        $expected = 'This is a message to you.';
        $this->assertNotSame($expected, $message->invoke($this->logout, $expected));
    }
}
