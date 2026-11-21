<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Admin;

use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Actions\Login;
use TheFrosty\WpLoginLocker\Actions\Logout;
use WP_Admin_Bar;
use function __;
use function add_query_arg;
use function admin_url;
use function esc_html__;
use function esc_js;
use function esc_url;
use function get_current_user_id;
use function sprintf;
use function wp_nonce_url;

/**
 * Class Menu
 * @package TheFrosty\WpLoginLocker\Admin
 */
class Menu extends AbstractLoginLocker
{

    public const string ID = 'login-locker-logout';

    public function addHooks(): void
    {
        $this->addAction('admin_bar_menu', [$this, 'adminBarMenu']);
    }

    /**
     * Register our custom logout action.
     * phpcs:disable Generic.Files.LineLength.TooLong
     * @param WP_Admin_Bar $wp_admin_bar
     */
    protected function adminBarMenu(WP_Admin_Bar $wp_admin_bar): void
    {
        $user_actions = $wp_admin_bar->get_node('user-actions');
        if ($user_actions) {
            $wp_admin_bar->add_node([
                'id' => self::ID,
                'parent' => $user_actions->id,
                'title' => esc_html__('Secure Log Out', 'wp-login-locker'),
                'href' => esc_url(
                    wp_nonce_url(
                        add_query_arg(
                            ['action' => Logout::ACTION, 'user_id' => get_current_user_id()],
                            admin_url('admin.php')
                        ),
                        sprintf(Logout::ACTION_S, get_current_user_id()),
                        Login::ADMIN_ACTION_NONCE
                    )
                ),
                'meta' => [
                    'onclick' => sprintf(
                        'return confirm("%s")',
                        esc_js(
                            __(
                                'Continue to secure log out? You will need a login link from a Login Locker email to access wp-login.php again.',
                                'wp-login-locker'
                            )
                        )
                    ),
                ],
            ]);
        }
    }
}
