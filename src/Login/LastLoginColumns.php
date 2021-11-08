<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Login;

use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class LastLoginColumns
 * Based on WP Last Login by Konstantin
 *
 * @link https://wordpress.org/plugins/wp-last-login/
 *
 * @package TheFrosty\WpLoginLocker\Login
 */
class LastLoginColumns implements WpHooksInterface
{
    use HooksTrait;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        // Make sure only 'Admins' who can `list_users` see the new columns.
        if (\current_user_can('list_users')) {
            $this->addAction('manage_site-users-network_columns', [$this, 'addColumn'], 1);
            $this->addAction('manage_users_columns', [$this, 'addColumn'], 1);
            $this->addAction('wpmu_users_columns', [$this, 'addColumn'], 1);
            $this->addAction('manage_users_custom_column', [$this, 'manageUsersCustomColumn'], 10, 3);
            $this->addAction('manage_users_sortable_columns', [$this, 'addSortable']);
            $this->addAction('manage_users-network_sortable_columns', [$this, 'addSortable']);
            $this->addAction('pre_get_users', [$this, 'preGetUsers']);
        }
    }

    /**
     * Adds the last login column to the network admin user list.
     * @param  array $cols The default columns.
     * @return array
     */
    protected function addColumn(array $cols): array
    {
        $cols[LoginLocker::LAST_LOGIN] = \esc_html__('Last Login', 'wp-login-locker');

        return $cols;
    }


    /**
     * Adds the last login column to the network admin user list.
     * @param string|null $value Value of the custom column.
     * @param string $column_name The name of the column.
     * @param int $user_id The user's id.
     * @return string
     */
    protected function manageUsersCustomColumn(?string $value, string $column_name, int $user_id): string
    {
        if ($column_name === LoginLocker::LAST_LOGIN) {
            $value = \esc_html__('Unknown', 'wp-login-locker');
            $last_login = \get_user_meta($user_id, LoginLocker::LAST_LOGIN_TIME_META_KEY);

            if (!empty($last_login)) {
                $value = \date_i18n(\get_option('date_format'), \end($last_login));
            }
        }

        return $value ?? '';
    }


    /**
     * Register the column as sortable.
     * @param array $columns
     * @return array
     */
    protected function addSortable(array $columns): array
    {
        $columns[LoginLocker::LAST_LOGIN] = LoginLocker::LAST_LOGIN;

        return $columns;
    }


    /**
     * Handle ordering by last login.
     * @param \WP_User_Query $user_query Request arguments.
     * @return \WP_User_Query
     */
    protected function preGetUsers(\WP_User_Query $user_query): \WP_User_Query
    {
        if (isset($user_query->query_vars['orderby']) &&
            $user_query->query_vars['orderby'] === LoginLocker::LAST_LOGIN
        ) {
            $user_query->query_vars = \array_merge(
                $user_query->query_vars,
                [
                    'meta_key' => LoginLocker::LAST_LOGIN_TIME_META_KEY,
                    'orderby' => 'meta_value_num',
                ]
            );
        }

        return $user_query;
    }
}
