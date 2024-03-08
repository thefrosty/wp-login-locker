<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Login;

use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpUtilities\Api\Hash;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use function TheFrosty\WpLoginLocker\Helpers\terminate;

/**
 * Class WpLogin
 * For all things related to the WordPress login.
 * @package TheFrosty\WpLoginLocker\Login
 */
class WpLogin extends AbstractLoginLocker
{

    use GeoUtilTrait, Hash, HooksTrait;

    public const AUTH_CHECK_KEY = 'auth_check';
    public const AUTH_CHECK_IS_ENCRYPTED_KEY = 'auth_encrypted';
    public const COOKIE_NAME = LoginLocker::META_PREFIX . self::AUTH_CHECK_KEY;
    public const COOKIE_VALUE_S = 'OK|%s';
    public const COOKIE_EXPIRE = '+1 year';

    protected const ENCRYPTION_DELIMITER = '|';
    private const ENCRYPTION_KEY = 'WpL0gin' . self::ENCRYPTION_DELIMITER;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('login_init', [$this, 'loginAuthCheck']);
        $this->addFilter('login_message', [$this, 'lostPasswordMessage'], 11);
    }

    /**
     * Method for plugin activation.
     */
    public function activate(): void
    {
        if (\is_user_logged_in()) {
            $this->setLoginCookie(\wp_get_current_user(), 'user_email');
            $this->addDefaultUserMeta();
        }
    }

    /**
     * This method, called on `login_init` validates the user and allows or
     * denys access to the wp-login.php page.
     *
     * If the $_GET variable defined as a class const is set and that value
     * is a valid user on the site, set a cookie and allow them to access the login
     * form. Otherwise, send them a fake html without the login form.
     */
    protected function loginAuthCheck(): void
    {
        // Make sure the current action of logout is allowed (if a user changes their login name or email).
        $query = $this->getRequest()->query;
        if (
            $query->has('action') &&
            $query->get('action') === 'logout' &&
            $query->has('_wpnonce') &&
            \wp_verify_nonce($query->get('_wpnonce'), 'log-out')
        ) {
            return;
        }

        $has_auth = false;
        if ($query->has(self::AUTH_CHECK_KEY) && !empty($query->get(self::AUTH_CHECK_KEY))) {
            $encrypted = \filter_var($query->get(self::AUTH_CHECK_IS_ENCRYPTED_KEY), \FILTER_VALIDATE_BOOL);
            $value = $encrypted === false ?
                $query->get(self::AUTH_CHECK_KEY) :
                $this->decrypt($query->get(self::AUTH_CHECK_KEY), \home_url());

            [$user, $field] = \array_values($this->getUserBy(\sanitize_text_field(\wp_unslash($value))));

            /**
             * If the $user exists, set a cookie in the browser so they have access
             * to the login page for `self::COOKIE_EXPIRE` time.
             */
            if ($user instanceof \WP_User && isset($user->$field)) {
                $has_auth = true;
                $this->setLoginCookie($user, $field);
            }
        } elseif ($this->getRequest()->cookies->has(self::COOKIE_NAME)) {
            // Validate the cookie
            $cookie = $this->decrypt($this->getRequest()->cookies->get(self::COOKIE_NAME), self::ENCRYPTION_KEY);
            [, $cookie_value] = \explode(self::ENCRYPTION_DELIMITER, $cookie);
            if (!empty($cookie_value)) {
                [$user] = \array_values(
                    $this->getUserBy($cookie_value)
                );
                if ($user instanceof \WP_User) {
                    $has_auth = true;
                } else {
                    // Maybe the user changed their login name or email, delete the cookie.
                    unset($_COOKIE[self::COOKIE_NAME]);
                    if (!\headers_sent()) {
                        \setcookie(self::COOKIE_NAME, '', \time() - \HOUR_IN_SECONDS);
                    }
                }
            }
        }

        if (!$has_auth) {
            $this->noAuthLoginHtml();
        }
    }

    /**
     * Replace the custom message HTML created from the Expire Passwords plugin.
     * Replaces the line break and empty paragraph tag with the default p.message entity.
     * Called on a priority higher than `lost_password_message` of 10.
     *
     * @param string $message
     *
     * @return string
     */
    protected function lostPasswordMessage($message): string
    {
        if (!$this->isLostPassOrExpired()) {
            return $message;
        }

        // \Expire_Passwords_Login_Screen::lost_password_message():109
        return \str_replace('<br><p>', '<p class="message">', $message);
    }

    /**
     * Render the fake login HTML and send the response to the page.
     * Sets a 403 Forbidden Status code and terminates all processes.
     */
    private function noAuthLoginHtml(): never
    {
        \ob_start();
        include $this->getPlugin()->getDirectory() . 'templates/login/wp-login.php';
        $content = \ob_get_clean();

        (new Response())
            ->setContent($content)
            ->setStatusCode(Response::HTTP_FORBIDDEN)
            ->sendHeaders()
            ->send();
        terminate();
    }

    /**
     * Is the current URL a lost password or expired request?
     *
     * @return bool
     */
    private function isLostPassOrExpired(): bool
    {
        $action = \filter_input(INPUT_GET, 'action', \FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $status = \filter_input(INPUT_GET, 'expass', \FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return 'lostpassword' === $action || 'expired' === $status;
    }

    /**
     * Helper to return an array of user data and the flag type used.
     *
     * @param string $input Incoming user credentials, email or login.
     *
     * @return array An array of user: WP_User object of false, and the field type flag for the
     *     WP_User object data.
     */
    private function getUserBy(string $input): array
    {
        if (\is_email($input) !== false) {
            $field = 'email';
            $user = \get_user_by($field, \sanitize_email($input));
        } else {
            $field = 'login';
            $user = \get_user_by($field, \sanitize_user($input));
        }

        return ['user' => $user, 'field' => 'user_' . $field];
    }

    /**
     * Returns a encrypted hash from the incoming value and our defined
     * class COOKIE_VALUE.
     *
     * @param string $value
     *
     * @return string
     */
    private function getCookieValue(string $value): string
    {
        return $this->encrypt(\sprintf(self::COOKIE_VALUE_S, $value), self::ENCRYPTION_KEY);
    }

    /**
     * Set's the user login cookie.
     *
     * @param \WP_User $user WP_User object.
     * @param string $field WP_User object property field.
     */
    private function setLoginCookie(\WP_User $user, string $field): void
    {
        /**
         * Dev note, you can't use Symfony's Response()->headers->setCookie( new Cookie( 'name', 'value' ) )
         * because it sends the headers, which then makes the login page throw a message notice
         * that the headers have already been sent. So, default `setcookie` is used.
         */
        if (!\headers_sent()) {
            \setcookie(
                self::COOKIE_NAME,
                $this->getCookieValue($user->$field),
                \strtotime(self::COOKIE_EXPIRE),
                \COOKIEPATH,
                \is_string(\COOKIE_DOMAIN) ? \COOKIE_DOMAIN : \parse_url(\home_url(), PHP_URL_HOST),
                ('https' === \parse_url(\wp_login_url(), \PHP_URL_SCHEME))
            );
        }
    }

    /**
     * Activation method to add the meta data to the current user.
     */
    private function addDefaultUserMeta(): void
    {
        NewUser::addLoginUserMeta(\get_current_user_id(), $this->getIp());
    }
}
