<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Actions;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\Login;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\WpMail\WpMail;

/**
 * Class NewUserTest
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 * @group actions
 */
class NewUserTest extends TestCase
{

    /**
     * @var NewUser $newUser
     */
    private $newUser;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->newUser = new NewUser();
        $this->newUser->setPlugin($this->plugin);
        $this->newUser->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->newUser);
    }

    public function tearDown(): void
    {
        unset($this->newUser);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->newUser, 'addHooks'));
        $this->newUser->addHooks();
    }

    /**
     * Test userRegisterAction().
     */
    public function testUserRegisterAction(): void
    {
        $this->assertTrue(\method_exists($this->newUser, 'userRegisterAction'));
        try {
            $userRegisterAction = $this->reflection->getMethod('userRegisterAction');
            $userRegisterAction->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            $userRegisterAction->invoke($this->newUser, $user->ID);
            $actual = \get_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY, true);
            $this->assertSame($this->newUser->getIP(), $actual);
            $this->deleteUserMeta($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * @param int $user_id
     */
    protected function deleteUserMeta(int $user_id): void
    {
        \delete_user_meta($user_id, LoginLocker::LAST_LOGIN_IP_META_KEY);
        \delete_user_meta($user_id, LoginLocker::LAST_LOGIN_TIME_META_KEY);
    }
}
