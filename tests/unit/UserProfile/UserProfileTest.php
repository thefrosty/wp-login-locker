<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\UserProfile;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\UserProfile\UserProfile;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;

/**
 * Class UserProfileTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 */
#[CoversClass(UserProfile::class)]
#[CoversClass(NewUser::class)]
#[CoversTrait(GeoUtilTrait::class)]
#[Group('user-profile')]
class UserProfileTest extends TestCase
{

    private UserProfile $userProfile;

    /**
     * Setup.
     */
    #[Override]
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

    #[Override]
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
            $fields = $this->reflection->getProperty('fields');
            $key = $fields->getValue($this->userProfile)[0];
            $saveExtraProfileFields = $this->reflection->getMethod('saveExtraProfileFields');
            $user = self::factory()->user->create_and_get();
            \wp_set_current_user($user->ID);
            $this->assertNotContains(
                'value',
                $getUserMeta->invoke($this->userProfile, $user->ID, $key)
            );
            $this->userProfile->getRequest()->request->set($key, 'value');
            $saveExtraProfileFields->invoke($this->userProfile, $user->ID);
            $this->assertContains(
                'value',
                $getUserMeta->invoke($this->userProfile, $user->ID, $key)
            );
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
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
            $user = self::factory()->user->create_and_get();
            $saveExtraProfileFields->invoke($this->userProfile, $user->ID);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
        }
    }
}
