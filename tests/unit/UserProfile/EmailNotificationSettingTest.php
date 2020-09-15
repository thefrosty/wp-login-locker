<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\UserProfile;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\UserProfile\EmailNotificationSetting;

/**
 * Class WpSignupTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group user-profile
 */
class EmailNotificationSettingTest extends TestCase
{

    /**
     * @var EmailNotificationSetting $emailNotificationSetting
     */
    private $emailNotificationSetting;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->emailNotificationSetting = new EmailNotificationSetting();
        $this->emailNotificationSetting->setPlugin($this->plugin);
        $this->emailNotificationSetting->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->emailNotificationSetting);
    }

    public function tearDown(): void
    {
        unset($this->emailNotificationSetting);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(\method_exists($this->emailNotificationSetting, 'addHooks'));
        $provider = $this->getMockProvider(EmailNotificationSetting::class);
        $provider->expects($this->exactly(5))
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var EmailNotificationSetting $provider */
        $provider->addHooks();
    }

    /**
     * Test showExtraUserFields().
     */
    public function testShowExtraUserFields(): void
    {
        $this->assertTrue(\method_exists($this->emailNotificationSetting, 'showExtraUserFields'));
        try {
            $showExtraUserFields = $this->reflection->getMethod('showExtraUserFields');
            $showExtraUserFields->setAccessible(true);
            \ob_start();
            $showExtraUserFields->invoke($this->emailNotificationSetting, null);
            $actual = \ob_get_clean();
            $this->assertEmpty($actual);
            $this->assertStringNotContainsString(LoginLocker::USER_EMAIL_META_KEY, $actual);
            $this->assertStringNotContainsString('Login Notifications', $actual);
            $user = self::factory()->user->create_and_get();
            \ob_start();
            $showExtraUserFields->invoke($this->emailNotificationSetting, $user);
            $actual = \ob_get_clean();
            $this->assertIsString($actual);
            $this->assertStringContainsString(LoginLocker::USER_EMAIL_META_KEY, $actual);
            $this->assertStringContainsString('Login Notifications', $actual);
            \wp_delete_user($user->ID);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
