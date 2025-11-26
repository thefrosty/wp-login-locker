<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\LoginLocker;

/**
 * Class NewUserTest
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 */
#[CoversClass(NewUser::class)]
#[Group('actions')]
class NewUserTest extends TestCase
{
    private NewUser $newUser;

    /**
     * Setup.
     */
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->newUser = new NewUser();
        $this->newUser->setPlugin($this->plugin);
        $this->newUser->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->newUser);
    }

    #[\Override]
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
        $provider = $this->getMockProvider(NewUser::class);
        $provider->expects($this->exactly(1))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var NewUser $provider */
        $provider->addHooks();
    }

    /**
     * Test userRegisterAction().
     */
    public function testUserRegisterAction(): void
    {
        $this->assertTrue(\method_exists($this->newUser, 'userRegisterAction'));
        try {
            $userRegisterAction = $this->reflection->getMethod('userRegisterAction');
            $user = self::factory()->user->create_and_get();
            $userRegisterAction->invoke($this->newUser, $user->ID);
            $actual = \get_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY, true);
            $this->assertSame($this->newUser->getIP(), $actual);
            $this->deleteUserMeta($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
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
