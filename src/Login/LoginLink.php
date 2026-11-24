<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Login;

use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\WpMail\WpMail;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use WP_User;
use function __;
use function apply_filters;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_option;
use function get_transient;
use function get_user_by;
use function is_email;
use function md5;
use function ob_get_clean;
use function ob_start;
use function sanitize_email;
use function sanitize_user;
use function set_transient;
use function sprintf;
use function time;
use function wp_date;
use function wp_json_encode;
use const MINUTE_IN_SECONDS;

/**
 * Class LoginLink
 * Emails a user a signed, expiring wp-login.php link, so a locked-out user (fresh install, new device,
 * expired cookie or after a secure log out) can request access. Requests are rate limited per IP and per
 * user, and the caller always shows the same response so accounts can't be enumerated.
 * @package TheFrosty\WpLoginLocker\Login
 */
final class LoginLink implements PluginAwareInterface
{

    use PluginAwareTrait;

    public const string REQUEST_KEY = LoginLocker::META_PREFIX . 'request_link';
    public const string REQUEST_FIELD = LoginLocker::META_PREFIX . 'request_link_user';
    public const string TRANSIENT_IP_PREFIX = LoginLocker::META_PREFIX . 'link_ip_';
    public const string TRANSIENT_USER_PREFIX = LoginLocker::META_PREFIX . 'link_user_';

    public const string FORM_ID = LoginLocker::META_PREFIX . 'request_link_form';
    public const string LOCAL_STORAGE_KEY = LoginLocker::META_PREFIX . 'request_link_sent';
    public const string NOTICE_ID = LoginLocker::META_PREFIX . 'request_link_notice';

    public const string FILTER_LINK_TTL = LoginLocker::HOOK_PREFIX . 'login_link_ttl';

    /** How long a magic login link stays valid, in seconds. */
    public const int LINK_TTL = 15 * MINUTE_IN_SECONDS;

    /** Max link requests per IP address per IP_WINDOW. */
    public const int IP_LIMIT = 5;
    public const int IP_WINDOW = 15 * MINUTE_IN_SECONDS;

    /** Only one email per user per USER_WINDOW (prevents email bombing a user). */
    public const int USER_WINDOW = 5 * MINUTE_IN_SECONDS;

    /**
     * Get the Unix timestamp a new magic login link for the user expires at.
     * @param WP_User $user
     * @return int
     */
    public static function getExpires(WP_User $user): int
    {
        /**
         * Filter how long a magic login link (from the "Email me a login link" form or WP-CLI) stays valid.
         * @param int $ttl Seconds. Default 15 minutes.
         * @param WP_User $user
         */
        $ttl = (int)apply_filters(self::FILTER_LINK_TTL, self::LINK_TTL, $user);

        return time() + ($ttl > 0 ? $ttl : self::LINK_TTL);
    }

    /**
     * Get the inline script that remembers (in localStorage) when this browser last submitted the
     * "Email me a login link" form, and hides the form until USER_WINDOW has passed. An extra check
     * on top of the server-side rate limits: it saves requests from browsers that keep retrying, but
     * can be bypassed by clearing storage, so the server limits stay the real protection.
     * @return string
     */
    public static function getLocalStorageScript(): string
    {
        return sprintf(
            '(function(key,waitSeconds,formId,noticeId){' .
            'var form=document.getElementById(formId),notice=document.getElementById(noticeId),last=0;' .
            'if(!form){return;}' .
            'try{last=parseInt(window.localStorage.getItem(key),10)||0;}catch(e){}' .
            'if(last&&Date.now()-last<waitSeconds*1000){form.hidden=true;if(notice){notice.hidden=false;}return;}' .
            'form.addEventListener("submit",function(){' .
            'try{window.localStorage.setItem(key,String(Date.now()));}catch(e){}' .
            '});' .
            '})(%1$s,%2$d,%3$s,%4$s);',
            wp_json_encode(self::LOCAL_STORAGE_KEY),
            self::USER_WINDOW,
            wp_json_encode(self::FORM_ID),
            wp_json_encode(self::NOTICE_ID)
        );
    }

    /**
     * Get the inline script that forgets this browser's last login link request (see
     * getLocalStorageScript()), so the form isn't hidden after the user got in, e.g. a Secure Log Out
     * shortly after using the link.
     * @return string
     */
    public static function getClearLocalStorageScript(): string
    {
        return sprintf(
            '(function(key){try{window.localStorage.removeItem(key);}catch(e){}})(%s);',
            wp_json_encode(self::LOCAL_STORAGE_KEY)
        );
    }

    /**
     * Find a user by email address or username.
     * @param string $login
     * @return WP_User|null
     */
    public static function findUser(string $login): ?WP_User
    {
        if ($login === '') {
            return null;
        }

        $user = is_email($login) !== false ?
            get_user_by('email', sanitize_email($login)) :
            get_user_by('login', sanitize_user($login));

        return $user instanceof WP_User ? $user : null;
    }

    /**
     * Handle a login link request from the locked wp-login.php page. Sends nothing (silently) for unknown
     * accounts or when rate limited; the caller must respond the same way regardless.
     * @param string $login Username or email address.
     * @param string|null $ip The requester's IP address.
     * @return bool Whether an email was sent (for tests; don't expose to the requester).
     */
    public function request(string $login, ?string $ip): bool
    {
        if (!$this->acquireIpSlot($ip)) {
            return false;
        }

        $user = self::findUser($login);
        if (!$user instanceof WP_User) {
            return false;
        }

        $user_key = self::TRANSIENT_USER_PREFIX . $user->ID;
        if (get_transient($user_key) !== false) {
            return false;
        }
        set_transient($user_key, $user->ID, self::USER_WINDOW);

        return $this->send($user, $ip);
    }

    /**
     * Email the user a signed login link.
     * @param WP_User $user
     * @param string|null $ip The requester's IP address, if known.
     * @return bool Whether the email was sent.
     */
    public function send(WP_User $user, ?string $ip = null): bool
    {
        $expires = self::getExpires($user);
        $wp_mail = new WpMail();
        $wp_mail->setPlugin($this->getPlugin());
        $wp_mail->__set(
            'pretext',
            sprintf(esc_html__('Your login link for %s.', 'wp-login-locker'), esc_html($wp_mail->getFromName()))
        );

        ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/email/messages/action-login-link.php';
        $content = (string)ob_get_clean();

        /**
         * %1$s User display name
         * %2$s Site name
         * %3$s Login URL
         * %4$s Link expiration date
         * %5$s IP address the link was requested from
         */
        $message = sprintf(
            $content,
            esc_html($user->display_name !== '' ? $user->display_name : $user->user_login),
            esc_html($wp_mail->getFromName()),
            esc_url(WpLogin::getAuthCheckUrl($user, $expires)),
            esc_html((string)wp_date(get_option('date_format') . ' ' . get_option('time_format'), $expires)),
            esc_html($ip ?? __('Unknown', 'wp-login-locker'))
        );

        return $wp_mail->send(
            $user->user_email,
            sprintf(esc_html__('Your login link for %s', 'wp-login-locker'), $wp_mail->getFromName()),
            $message
        );
    }

    /**
     * Rate limit requests per IP address (requests with an unknown IP share one bucket).
     * @param string|null $ip
     * @return bool
     */
    private function acquireIpSlot(?string $ip): bool
    {
        $key = self::TRANSIENT_IP_PREFIX . md5((string)$ip);
        $count = (int)get_transient($key);
        if ($count >= self::IP_LIMIT) {
            return false;
        }
        set_transient($key, $count + 1, self::IP_WINDOW);

        return true;
    }
}
