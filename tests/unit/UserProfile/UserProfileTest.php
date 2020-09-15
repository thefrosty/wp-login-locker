<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\UserProfile;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\UserProfile\LastLogin;
use TheFrosty\WpLoginLocker\UserProfile\UserProfile;

/**
 * Class UserProfileTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group user-profile
 */
class UserProfileTest extends TestCase
{

    /**
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userProfile = new class() extends UserProfile {
            public function __construct()
            {
                $this->fields = [
                    'someDummyKeyToSave',
                    'iShouldBeDeleted',
                ];
            }
        };
        $this->userProfile->setPlugin($this->plugin);
        $this->userProfile->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->userProfile);
    }

    public function tearDown(): void
    {
        unset($this->userProfile);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->userProfile, 'addHooks'));
        $provider = $this->getMockProvider(UserProfile::class);
        $provider->expects($this->exactly(4))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var UserProfile $provider */
        $provider->addHooks();
    }

    /**
     * Test doUserProfileAction().
     */
    public function testDoUserProfileAction(): void
    {
        $this->assertTrue(\method_exists($this->userProfile, 'doUserProfileAction'));
        try {
            $doUserProfileAction = $this->reflection->getMethod('doUserProfileAction');
            $doUserProfileAction->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            \ob_start();
            $doUserProfileAction->invoke($this->userProfile, null);
            $actual = \ob_get_clean();
            $this->assertStringContainsString('Login Locker Settings', $actual);
            $this->assertEquals(1, \did_action(UserProfile::USER_PROFILE_HOOK));
            \ob_start();
            $doUserProfileAction->invoke($this->userProfile, $user);
            $actual = \ob_get_clean();
            $this->assertStringNotContainsString('Login Locker Settings', $actual);
            $this->assertEquals(1, \did_action(UserProfile::USER_PROFILE_HOOK));
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test saveExtraProfileFields().
     */
    public function testSaveExtraProfileFields(): void
    {
        $this->assertTrue(\method_exists($this->userProfile, 'saveExtraProfileFields'));
        try {
            $getUserMeta = $this->reflection->getMethod('getUserMeta');
            $getUserMeta->setAccessible(true);
            $fields = $this->reflection->getProperty('fields');
            $fields->setAccessible(true);
            $key = $fields->getValue($this->userProfile)[0];
            $saveExtraProfileFields = $this->reflection->getMethod('saveExtraProfileFields');
            $saveExtraProfileFields->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            \wp_set_current_user($user->ID);
            $this->assertFalse(
                \in_array(
                    'value',
                    $getUserMeta->invoke($this->userProfile, $user->ID, $key),
                    true
                )
            );
            $this->userProfile->getRequest()->request->set($key, 'value');
            $saveExtraProfileFields->invoke($this->userProfile, $user->ID);
            $this->assertTrue(
                \in_array(
                    'value',
                    $getUserMeta->invoke($this->userProfile, $user->ID, $key),
                    true
                )
            );
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test saveExtraProfileFields().
     */
    public function testSaveExtraProfileFieldsEmpty(): void
    {
        $this->assertTrue(\method_exists($this->userProfile, 'saveExtraProfileFields'));
        try {
            $saveExtraProfileFields = $this->reflection->getMethod('saveExtraProfileFields');
            $saveExtraProfileFields->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            $saveExtraProfileFields->invoke($this->userProfile, $user->ID);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
