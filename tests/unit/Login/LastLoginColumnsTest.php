<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Login;

use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Login\LastLoginColumns;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\WpMail\WpMail;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 * @group login
 */
class LastLoginColumnsTest extends TestCase
{

    /**
     * @var LastLoginColumns $lastLoginColumns
     */
    private $lastLoginColumns;

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->lastLoginColumns = new LastLoginColumns();
        $this->reflection = $this->getReflection($this->lastLoginColumns);
    }

    public function tearDown(): void
    {
        unset($this->lastLoginColumns);
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->assertTrue(\method_exists($this->lastLoginColumns, 'addHooks'));
        $this->lastLoginColumns->addHooks();
    }

    /**
     * Test addColumn().
     */
    public function testAddColumn(): void
    {
        $this->assertTrue(\method_exists($this->lastLoginColumns, 'addColumn'));
        try {
            $addColumn = $this->reflection->getMethod('addColumn');
            $addColumn->setAccessible(true);
            $actual = $addColumn->invoke($this->lastLoginColumns, []);
            $this->assertIsArray($actual);
            $this->assertCount(1, $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test manageUsersCustomColumn().
     */
    public function testManageUsersCustomColumn(): void
    {
        $this->assertTrue(\method_exists($this->lastLoginColumns, 'manageUsersCustomColumn'));
        try {
            $manageUsersCustomColumn = $this->reflection->getMethod('manageUsersCustomColumn');
            $manageUsersCustomColumn->setAccessible(true);
            $user = self::factory()->user->create_and_get();
            \delete_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY);
            $actual = $manageUsersCustomColumn->invoke($this->lastLoginColumns, '', 'bad_key', $user->ID);
            $this->assertIsString($actual);
            $this->assertSame('', $actual);
            $actual = $manageUsersCustomColumn->invoke($this->lastLoginColumns, '', LoginLocker::LAST_LOGIN, $user->ID);
            $this->assertIsString($actual);
            $this->assertSame('Unknown', $actual);
            \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY, '');
            $actual = $manageUsersCustomColumn->invoke($this->lastLoginColumns, '', LoginLocker::LAST_LOGIN, $user->ID);
            $this->assertIsString($actual);
            $this->assertNotSame('Unknown', $actual);
            \delete_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test addSortable().
     */
    public function testAddSortable(): void
    {
        $this->assertTrue(\method_exists($this->lastLoginColumns, 'addSortable'));
        try {
            $addSortable = $this->reflection->getMethod('addSortable');
            $addSortable->setAccessible(true);
            $actual = $addSortable->invoke($this->lastLoginColumns, []);
            $this->assertIsArray($actual);
            $this->assertCount(1, $actual);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }

    /**
     * Test preGetUsers().
     */
    public function testPeGetUsers(): void
    {
        $this->assertTrue(\method_exists($this->lastLoginColumns, 'preGetUsers'));
        try {
            $preGetUsers = $this->reflection->getMethod('preGetUsers');
            $preGetUsers->setAccessible(true);
            $WP_User_Query = new \WP_User_Query();
            $actual = $preGetUsers->invoke($this->lastLoginColumns, $WP_User_Query);
            $this->assertInstanceOf(\WP_User_Query::class, $actual);
            $WP_User_Query->query_vars['orderby'] = LoginLocker::LAST_LOGIN;
            $actual = $preGetUsers->invoke($this->lastLoginColumns, $WP_User_Query);
            $this->assertIsArray($actual->query_vars);
            $this->assertArrayHasKey('meta_key', $actual->query_vars);
        } catch (\ReflectionException $exception) {
            $this->assertInstanceOf(\ReflectionException::class, $exception);
            $this->markAsRisky();
        }
    }
}
