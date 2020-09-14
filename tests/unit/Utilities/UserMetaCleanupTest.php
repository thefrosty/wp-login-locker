<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Utilities;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpLoginLocker\Utilities\UserMetaCleanup;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;

/**
 * Class UserMetaCleanupTest
 * @package TheFrosty\Tests\WpLoginLocker\WpCore
 * @group utilities
 */
class UserMetaCleanupTest extends TestCase
{

    /**
     * @var UserMetaCleanup $userMetaCleanup
     */
    private $userMetaCleanup;

    private $user_id;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->user_id = self::factory()->user->create();
        foreach (\range(1, 15) as $i) {
            \update_user_meta($this->user_id, LoginLocker::LAST_LOGIN_IP_META_KEY, \sprintf('10.0.0.%d', $i));
        }
        $this->userMetaCleanup = new UserMetaCleanup( $this->user_id);
    }

    public function tearDown(): void
    {
        \delete_user_meta($this->user_id, LoginLocker::LAST_LOGIN_IP_META_KEY);
        unset($this->userMetaCleanup, $this->user_id);
        parent::tearDown();
    }

    /**
     * Test cleanup().
     */
    public function testCleanup(): void
    {
        $this->assertTrue(\method_exists($this->userMetaCleanup, 'cleanup'));
        $this->userMetaCleanup->cleanup();
    }
}
