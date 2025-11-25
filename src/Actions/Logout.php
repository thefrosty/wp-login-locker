<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Actions;

use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpLoginLocker\LoginLocker;
use function esc_html__;
use function sprintf;
use function wp_login_url;
use function wp_safe_redirect;
use function wp_verify_nonce;
use const FILTER_VALIDATE_BOOL;

/**
 * Class Login
 * @package TheFrosty\WpLoginLocker\Actions
 */
class Logout extends AbstractLoginLocker
{

    public const string ACTION = 'login_locker_logout';
    public const string ACTION_S = 'locker_logout_user_id_%d';

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('admin_action_' . self::ACTION, [$this, 'maybeLogout']);
        $this->addFilter(LoginLocker::HOOK_PREFIX . 'wp-login/message', [$this, 'message']);
    }

    protected function maybeLogout(): void
    {
        $query = $this->getRequest()->query;
        if (
            $query->has(Login::ADMIN_ACTION_NONCE) &&
            wp_verify_nonce(
                $query->get(Login::ADMIN_ACTION_NONCE),
                sprintf(self::ACTION_S, $query->has('user_id'))
            ) !== false
        ) {
            WpLogin::unsetCookie();
            wp_logout();
            wp_safe_redirect(add_query_arg(self::ACTION, '1', wp_login_url()));
            exit;
        }
    }

    /**
     * Modify the login message on secure log out.
     * @param string $message
     * @return string
     */
    protected function message(string $message): string
    {
        $query = $this->getRequest()->query;
        if (!$query->has(self::ACTION) || !filter_var($query->get(self::ACTION), FILTER_VALIDATE_BOOL)) {
            return $message;
        }

        return esc_html__(
            'Secure log out success, auth cookies have been removed.',
            'wp-login-locker'
        );
    }
}
