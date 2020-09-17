<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Actions;

use Dwnload\WpSettingsApi\Api\Options;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Settings\Settings;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpLoginLocker\Utilities\UserMetaCleanup;
use TheFrosty\WpLoginLocker\WpMail\WpMail;
use TheFrosty\WpUtilities\Plugin\HooksTrait;

/**
 * Class Login
 * @package TheFrosty\WpLoginLocker\Actions
 */
class Login extends AbstractLoginLocker
{

    use GeoUtilTrait, HooksTrait;

    public const ADMIN_ACTION_SEND_EMAIL = 'login-locker-send-email';
    public const ADMIN_ACTION_NONCE = '_lockerNonce';

    /**
     * @var WpMail $wp_mail
     */
    private $wp_mail;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('wp_login', [$this, 'wpLoginAction'], 10, 2);
        $this->addAction('admin_post_' . self::ADMIN_ACTION_SEND_EMAIL, [$this, 'sendTestEmail']);
        $this->addAction('login_locker_cleanup_last_login_meta', [$this, 'postMetaCleanup']);
        $this->addFilter('is_protected_meta', [$this, 'setProtectedMeta'], 10, 2);
    }

    /**
     * Create a email notifying the user someone has logged in (if their notifications aren't off).
     * Also adds user meta data of their IP address and login time.
     *
     * @param string $user_login
     * @param \WP_User $user
     */
    protected function wpLoginAction(string $user_login, \WP_User $user): void
    {
        $current_ip = $this->getIP();
        $last_login_ip = \get_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY);
        $user_notification = \get_user_meta($user->ID, LoginLocker::USER_EMAIL_META_KEY, true);

        /**
         * If the current IP does not match their last login IP
         * (and the user has login notifications 'on'), send a notification.
         */
        if ((!empty($last_login_ip) && $current_ip !== \end($last_login_ip)) && empty($user_notification)) {
            $this->wp_mail = new WpMail();
            $this->wp_mail->setPlugin($this->getPlugin());
            $this->wp_mail->__set('pretext', $this->getEmailPretext());
            $this->wp_mail->send(
                $user->user_email,
                \sprintf(\esc_html__('New login to %1$s account', 'wp-login-locker'), $this->getHomeUrl()),
                $this->getEmailMessage($user)
            );
        }

        /**
         * Action when a user logs-in you can hook into.
         *
         * @param string $current_ip The current users IP address.
         * @param array $last_login_ip An array of the users last login IP's.
         * @param mixed $user_notification Whether the users notification preferences are enabled.
         */
        \do_action(LoginLocker::HOOK_PREFIX . 'wp_login', $current_ip, $last_login_ip, $user_notification);

        /**
         * Update the current users login meta data
         * (regardless of current IP or notification settings)
         */
        \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_IP_META_KEY, $current_ip, false);
        \add_user_meta($user->ID, LoginLocker::LAST_LOGIN_TIME_META_KEY, \time(), false);
        unset($current_ip, $last_login_ip, $user_notification, $this->wp_mail);
        \wp_schedule_single_event(\time() + MINUTE_IN_SECONDS, 'login_locker_cleanup_last_login_meta', [$user->ID]);
    }

    /**
     * Action to send a test email. Triggered via 'admin-post.php?action=`self::ADMIN_ACTION_SEND_EMAIL`'.
     */
    protected function sendTestEmail(): void
    {
        $user = \wp_get_current_user();
        $query = $this->getRequest()->query;
        if (!($user instanceof \WP_User) ||
            !$query->has(self::ADMIN_ACTION_NONCE) ||
            \wp_verify_nonce($query->get(self::ADMIN_ACTION_NONCE), self::ADMIN_ACTION_SEND_EMAIL) !== 1 ||
            $user->ID === 0
        ) {
            \wp_die(
                \esc_html__('Couldn\'t send test email.', 'wp-login-locker'),
                '',
                ['response' => Response::HTTP_NOT_ACCEPTABLE]
            );
        }
        $this->wp_mail = new WpMail();
        $this->wp_mail->setPlugin($this->getPlugin());
        $this->wp_mail->__set('pretext', $this->getEmailPretext());
        $sent = $this->wp_mail->send(
            $user->user_email,
            \sprintf(\esc_html__('[TEST] New login to %1$s account', 'wp-login-locker'), $this->getHomeUrl()),
            $this->getEmailMessage($user)
        );
        $this->safeRedirect($sent);
    }

    /**
     * Trigger on a cron to cleanup old user meta.
     * @param int $user_id
     */
    protected function postMetaCleanup(int $user_id): void
    {
        (new UserMetaCleanup($user_id))->cleanup();
    }

    /**
     * Filters whether a meta key is protected.
     *
     * @param bool $protected
     * @param string $meta_key
     * @return bool
     * @uses is_protected_meta()
     */
    protected function setProtectedMeta($protected, $meta_key): bool
    {
        switch ($meta_key) {
            case LoginLocker::LAST_LOGIN_IP_META_KEY:
            case LoginLocker::LAST_LOGIN_TIME_META_KEY:
                $protected = true;
                break;
        }

        return $protected;
    }

    /**
     * Get the pretext content.
     *
     * @return string
     */
    private function getEmailPretext(): string
    {
        $content = Options::getOption(
            Settings::EMAIL_SETTING_PRETEXT,
            Settings::EMAIL_SETTINGS,
            null
        );
        if (empty($content)) {
            \ob_start();
            include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-pretext.php';
            $content = \ob_get_clean();
        }

        /**
         * %1$s Site name
         */
        return \sprintf($content, $this->wp_mail->getFromName());
    }

    /**
     * Get our notification message from our messages templates.
     * @param \WP_User $user
     * @return string
     */
    private function getEmailMessage(\WP_User $user): string
    {
        $content = Options::getOption(
            Settings::EMAIL_SETTING_MESSAGE,
            Settings::EMAIL_SETTINGS,
            null
        );
        if (empty($content)) {
            \ob_start();
            include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-notice.php';
            $content = \ob_get_clean();
        }

        /**
         * Add our auth check key and the users email so they can access the
         * login page if they don't have "access" by a cookie session. Force re-auth
         * on login URL render so they have to re-enter there credentials.
         */
        $login_url = \add_query_arg(
            [
                WpLogin::AUTH_CHECK_KEY => \sanitize_email($user->user_email),
            ],
            \wp_login_url('', true)
        );

        /**
         * %1$s User first and last name (display name)
         * %2$s User agent
         * %3$s IP address
         * %4$s Login URL
         * %5$s Site name
         * %6$s Site email
         */
        return \sprintf(
            $content,
            $this->getUserName($user),
            $this->getUserAgent(),
            $this->getIP(),
            \esc_url($login_url),
            $this->wp_mail->getFromName(),
            $this->wp_mail->getFromAddress()
        );
    }

    /**
     * Return a user name based on the current WP_User. Checks whether they
     * have setup their first name\ or, display name before using their login
     * user name.
     * @param \WP_User $user
     * @return string
     */
    private function getUserName(\WP_User $user): string
    {
        if (!empty($user->first_name)) {
            return $user->first_name;
        } elseif (!empty($user->display_name)) {
            return $user->display_name;
        }

        return $user->user_login;
    }

    /**
     * Returns the site url host.
     * @return string
     */
    private function getHomeUrl(): string
    {
        return \parse_url(\home_url(), \PHP_URL_HOST);
    }

    /**
     * Safe redirect.
     * @param bool $sent
     */
    private function safeRedirect(bool $sent): void
    {
        \wp_safe_redirect(\add_query_arg('sent', $sent, \wp_get_referer()));
        exit;
    }
}
