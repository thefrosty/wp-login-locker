<?php

namespace Dwnload\WpLoginLocker\Actions;

use Dwnload\WpLoginLocker\Login\LastLoginColumns;
use Dwnload\WpLoginLocker\Login\WpLogin;
use Dwnload\WpLoginLocker\Plugins\WpUserProfiles\UserEmailSection;
use Dwnload\WpLoginLocker\RequestsInterface;
use Dwnload\WpLoginLocker\Utilities\GeoUtilTrait;
use Dwnload\WpLoginLocker\WpMail\WpMail;
use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class Login
 * @package Dwnload\WpLoginLocker\Actions
 */
class Login extends AbstractHookProvider implements RequestsInterface, WpHooksInterface
{
    use GeoUtilTrait, HooksTrait;

    const SUBJECT = 'New login to Dwnload.io account';

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction('wp_login', [$this, 'wpLoginAction'], 10, 2);
    }

    /**
     * Create a email notifying the user someone has logged in.
     *
     * @param string $user_login
     * @param \WP_User $user
     */
    protected function wpLoginAction(string $user_login, \WP_User $user)
    {
        $current_ip = $this->getIP();
        $last_login_ip = \get_user_meta($user->ID, LastLoginColumns::LAST_LOGIN_IP_META_KEY, true);
        $user_notification = \get_user_meta($user->ID, UserEmailSection::USER_META_KEY, true);

        /**
         * If the current IP does not match their last login IP
         * (and the user has login notifications 'on'), send a notification.
         */
        if ($current_ip !== $last_login_ip && empty($user_notification)) {
            $mail = new WpMail();
            $mail->__set('pretext', $this->getEmailPretext());
            $mail->send($user->user_email, self::SUBJECT, $this->getEmailMessage($user));
        }

        /**
         * Update the current users login meta data
         * (regardless of current IP or notification settings)
         */
        \update_user_meta($user->ID, LastLoginColumns::LAST_LOGIN_IP_META_KEY, $current_ip, $last_login_ip);
        \update_user_meta($user->ID, LastLoginColumns::LAST_LOGIN_TIME_META_KEY, \time());
    }

    /**
     * Get the pretext content.
     *
     * @return string
     */
    private function getEmailPretext(): string
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-pretext.php';

        return \ob_get_clean();
    }

    /**
     * Get our notification message from our messages templates.
     *
     * @param \WP_User $user
     *
     * @return string
     */
    private function getEmailMessage(\WP_User $user): string
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-notice.php';
        $content = \ob_get_clean();

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
         * 1. User first and last name (display name)
         * 2. User Agent
         * 3. IP Address
         * 4. Login URL
         */
        return sprintf($content,
            $this->getUserName($user),
            $this->getUserAgent(),
            $this->getIP(),
            \esc_url($login_url)
        );
    }

    /**
     * Return a user name based on the current WP_User. Checks whether they
     * have setup their first name\ or, display name before using their login
     * user name.
     *
     * @param \WP_User $user
     *
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
}
