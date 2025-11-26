<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Admin;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\Tests\WpLoginLocker\Utilities\GeoUtilTraitTest;
use TheFrosty\WpLoginLocker\Actions\Logout;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\Admin\Menu;
use WP_Admin_Bar;
use function method_exists;
use function set_current_screen;
use function wp_set_current_user;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Admin
 */
#[CoversClass(Menu::class)]
#[Group('admin')]
#[UsesClass(Logout::class)]
#[UsesClass(NewUser::class)]
#[UsesTrait(GeoUtilTraitTest::class)]
class MenuTest extends TestCase
{

    private Menu $menu;

    public static function set_up_before_class(): void
    {
        require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->menu = new Menu();
        $this->reflection = $this->getReflection($this->menu);
        wp_set_current_user(self::factory()->user->create_and_get(['role' => 'administrator']));
        set_current_screen('dashboard');
    }

    #[Override]
    public function tearDown(): void
    {
        unset($this->menu);
        set_current_screen();
        parent::tearDown();
    }

    /**
     * Test addHooks().
     */
    public function testAddHooks(): void
    {
        $this->assertTrue(method_exists($this->menu, 'addHooks'));
        $provider = $this->getMockProvider(Menu::class);
        $provider->expects($this->once())
            ->method(self::METHOD_ADD_FILTER)
            ->willReturn(true);
        /** @var Menu $provider */
        $provider->addHooks();
    }

    /**
     * Test adminBarMenu().
     */
    public function testAdminBarMenu(): void
    {
        global $wp_admin_bar;
        $this->assertTrue(method_exists($this->menu, 'adminBarMenu'));
        $wp_admin_bar ??= new WP_Admin_Bar();
        $this->assertNull($this->reflection->getMethod('adminBarMenu')->invoke($this->menu, $wp_admin_bar));
    }

    /**
     * Test adminBarMenu().
     */
    public function testAdminBarMenuWithUserActions(): void
    {
        global $wp_admin_bar;
        $this->assertTrue(method_exists($this->menu, 'adminBarMenu'));
        $wp_admin_bar ??= new WP_Admin_Bar();
        $wp_admin_bar->add_node(
            [
                'id' => 'user-actions',
                'meta' => ['class' => 'user-actions'],
            ]
        );
        $this->assertNull($this->reflection->getMethod('adminBarMenu')->invoke($this->menu, $wp_admin_bar));
    }
}
