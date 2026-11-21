<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Login;

use Dwnload\WpSettingsApi\Api\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpLoginLocker\AbstractLoginLocker;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\Settings\Settings;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpLoginLocker\Utilities\SignedToken;
use TheFrosty\WpUtilities\Api\Hash;
use WP_User;
use function add_query_arg;
use function apply_filters;
use function array_values;
use function esc_html__;
use function explode;
use function filter_input;
use function filter_var;
use function get_current_user_id;
use function get_option;
use function get_user_by;
use function headers_sent;
use function home_url;
use function in_array;
use function is_email;
use function is_ssl;
use function is_string;
use function is_user_logged_in;
use function min;
use function ob_get_clean;
use function ob_start;
use function parse_url;
use function sanitize_email;
use function sanitize_text_field;
use function sanitize_user;
use function setcookie;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtotime;
use function substr;
use function TheFrosty\WpLoginLocker\Helpers\terminate;
use function time;
use function wp_get_current_user;
use function wp_login_url;
use function wp_unslash;
use function wp_verify_nonce;
use const COOKIE_DOMAIN;
use const COOKIEPATH;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;
use const FILTER_VALIDATE_BOOL;
use const HOUR_IN_SECONDS;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

/**
 * Class WpLogin
 * For all things related to the WordPress login.
 * @package TheFrosty\WpLoginLocker\Login
 */
class WpLogin extends AbstractLoginLocker
{

    use GeoUtilTrait;
    use Hash {
        Hash::getEncryptionKey as hashEncryptionKey;
    }

    public const string AUTH_CHECK_KEY = 'auth_check';
    public const string AUTH_CHECK_LINK_TTL = '+7 days';
    public const string FILTER_AUTH_CHECK_LINK_TTL = LoginLocker::HOOK_PREFIX . 'auth_check_link_ttl';
    public const string COOKIE_NAME = LoginLocker::META_PREFIX . self::AUTH_CHECK_KEY;
    public const string COOKIE_VALUE_S = 'OK|%s';
    public const string COOKIE_EXPIRE_S = '+%d %s';

    protected const string ENCRYPTION_DELIMITER = '|';
    private const string ENCRYPTION_KEY = 'WpL0gin' . self::ENCRYPTION_DELIMITER;

    private bool $has_auth = false;

    /**
     * Maybe the user changed their login name, email, or the Hash key has been changed/reset.
     * Delete our auth cookie.
     */
    public static function unsetCookie(): void
    {
        unset($_COOKIE[self::COOKIE_NAME]); // phpcs:ignore
        if (!headers_sent()) {
            setcookie(
                self::COOKIE_NAME,
                '',
                [
                    'expires' => time() - HOUR_IN_SECONDS,
                    'path' => COOKIEPATH,
                    'domain' => COOKIE_DOMAIN,
                    'secure' => is_ssl() && parse_url(get_option('home'), PHP_URL_SCHEME) === 'https',
                    'httponly' => true,
                ]
            );
        }
    }

    /**
     * Build a signed, expiring wp-login.php URL that grants the user access to the login form.
     * @param WP_User $user
     * @param int|null $expires Unix timestamp the link expires at. Defaults to getAuthCheckLinkExpires().
     * @return string
     */
    public static function getAuthCheckUrl(WP_User $user, ?int $expires = null): string
    {
        return add_query_arg(
            self::AUTH_CHECK_KEY,
            SignedToken::create($user, self::AUTH_CHECK_KEY, $expires ?? self::getAuthCheckLinkExpires($user)),
            wp_login_url('', true)
        );
    }

    /**
     * Get the Unix timestamp a new login link for the user expires at.
     * @param WP_User $user
     * @return int
     */
    public static function getAuthCheckLinkExpires(WP_User $user): int
    {
        /**
         * Filter how long a login link (sent in emails) stays valid.
         * @param string $ttl A relative strtotime() string. Default '+7 days'.
         * @param WP_User $user
         */
        $ttl = (string)apply_filters(
            self::FILTER_AUTH_CHECK_LINK_TTL,
            self::AUTH_CHECK_LINK_TTL,
            $user
        );
        $expires = strtotime($ttl);

        return $expires !== false ? $expires : strtotime(self::AUTH_CHECK_LINK_TTL);
    }

    /**
     * Get the access cookie lifetime from the "Cookie Expiration" and "Cookie Expiration Unit" settings,
     * as a relative strtotime() string, e.g. "+1 year" or "+2 week".
     * @return string
     */
    public static function getCookieExpire(): string
    {
        $number = (int)Options::getOption(
            Settings::GENERAL_SETTING_COOKIE_EXPIRE,
            Settings::GENERAL_SETTINGS,
            Settings::COOKIE_EXPIRE_DEFAULT
        );
        if ($number < 1) {
            $number = Settings::COOKIE_EXPIRE_DEFAULT;
        }

        $unit = Options::getOption(
            Settings::GENERAL_SETTING_COOKIE_EXPIRE_UNIT,
            Settings::GENERAL_SETTINGS,
            Settings::COOKIE_EXPIRE_UNIT_DEFAULT
        );
        if (!in_array($unit, Settings::COOKIE_EXPIRE_UNITS, true)) {
            $unit = Settings::COOKIE_EXPIRE_UNIT_DEFAULT;
        }

        return sprintf(self::COOKIE_EXPIRE_S, min($number, Settings::COOKIE_EXPIRE_MAX), $unit);
    }

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
        if (is_user_logged_in()) {
            $this->setLoginCookie(wp_get_current_user());
            $this->addDefaultUserMeta();
        }
    }

    /**
     * This method, called on `login_init` validates the user and allows or
     * denys access to the wp-login.php page.
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
            wp_verify_nonce($query->get('_wpnonce'), 'log-out')
        ) {
            return;
        }

        $this->maybeValidateLostPasswordUser();

        if ($query->has(self::AUTH_CHECK_KEY)) {
            $user = SignedToken::verify($query->all()[self::AUTH_CHECK_KEY] ?? null, self::AUTH_CHECK_KEY);

            /**
             * If the signed link is valid, set a cookie in the browser so they have access
             * to the login page for the "Cookie Expiration" settings' time (see getCookieExpire()).
             */
            if ($user instanceof WP_User) {
                $this->has_auth = true;
                $this->setLoginCookie($user);
            }
        } elseif ($this->getRequest()->cookies->has(self::COOKIE_NAME)) {
            // Validate the cookie: a signed, expiring token (or a legacy cookie that gets upgraded).
            $cookie = $this->getRequest()->cookies->all()[self::COOKIE_NAME] ?? null;
            $user = SignedToken::verify($cookie, self::COOKIE_NAME);
            if (!$user instanceof WP_User) {
                $user = $this->maybeUpgradeLegacyCookie($cookie);
            }
            if ($user instanceof WP_User) {
                $this->has_auth = true;
            } else {
                $delete_cookie = true;
            }
        }

        if (isset($delete_cookie)) {
            self::unsetCookie();
        }

        if (!$this->has_auth) {
            $this->noAuthLoginHtml($this->maybeRequestLoginLink());
        }
    }

    /**
     * Show the "Email me a login link" form? Not once it's been submitted, or when the request key is in the URL.
     * @return bool
     */
    private function showLoginLinkForm(): bool
    {
        return !$this->getRequest()->query->has(LoginLink::REQUEST_KEY);
    }

    /**
     * Handle the "Email me a login link" form on the locked page. Responds the same whether the account
     * exists or the request was rate limited, so accounts can't be enumerated.
     * @return string|null The message to show, or null when this isn't a login link request.
     */
    private function maybeRequestLoginLink(): ?string
    {
        $request = $this->getRequest();
        if (!$request->isMethod(Request::METHOD_POST) || !$request->query->has(LoginLink::REQUEST_KEY)) {
            return null;
        }

        $login = $request->request->all()[LoginLink::REQUEST_FIELD] ?? '';
        $login_link = new LoginLink();
        $login_link->setPlugin($this->getPlugin());
        $login_link->request(
            is_string($login) ? sanitize_text_field(wp_unslash($login)) : '',
            $this->getIP()
        );

        return esc_html__(
            'If that account exists, we\'ve emailed a login link to its email address.',
            'wp-login-locker'
        );
    }

    /**
     * Replace the custom message HTML created from the Expire Passwords plugin.
     * Replaces the line break and empty paragraph tag with the default p.message entity.
     * Called on a priority higher than `lost_password_message` of 10.
     * @param string $message
     * @return string
     */
    protected function lostPasswordMessage($message): string
    {
        if (!$this->isLostPassOrExpired()) {
            return $message;
        }

        // \Expire_Passwords_Login_Screen::lost_password_message():109
        return str_replace('<br><p>', '<p class="message">', $message);
    }

    /**
     * Render the fake login HTML and send the response to the page.
     * Sets a 403 Forbidden Status code and terminates all processes (unless los passwords are allowed).
     * @param string|null $message An (escaped) message to show instead of the default.
     */
    private function noAuthLoginHtml(?string $message = null): never
    {
        if ($this->isLostPasswordAllowed() && $this->isLostPassOrExpired()) {
            ob_start();
            include $this->getPlugin()->getDirectory() . 'templates/login/lostpassword.php';
            $content = ob_get_clean();
        }

        if (!isset($content)) {
            $show_login_link_form = $this->showLoginLinkForm();
            ob_start();
            include $this->getPlugin()->getDirectory() . 'templates/login/wp-login.php';
            $code = Response::HTTP_FORBIDDEN;
            $content = ob_get_clean();
        }

        new Response()
            ->setContent($content)
            ->setStatusCode($code ?? Response::HTTP_ACCEPTED)
            ->sendHeaders()
            ->send();
        terminate();
    }

    /**
     * Maybe validate the current request is a "lostpassword" action.
     */
    private function maybeValidateLostPasswordUser(): void
    {
        // Only when lost password is allowed, and only for a POSTed lost password request.
        if (
            !$this->isLostPasswordAllowed() ||
            !$this->isLostPassOrExpired() ||
            !$this->getRequest()->isMethod('POST')
        ) {
            return;
        }

        $request = $this->getRequest()->request;
        if (
            $this->getRequest()->query->has('login_locker') &&
            ($request->has('login_locker_user_login') && !empty($request->get('login_locker_user_login'))) &&
            (
                is_email($request->get('login_locker_user_login')) &&
                email_exists($request->get('login_locker_user_login'))
            )
        ) {
            [$user, $field] = array_values(
                $this->getUserBy(sanitize_text_field(wp_unslash($request->get('login_locker_user_login'))))
            );
            /**
             * If the $user exists, set a cookie in the browser so they have access
             * to the login page for the lost password "Timeout" setting's minutes.
             */
            if ($user instanceof WP_User && isset($user->$field)) {
                $this->has_auth = true;
                // Assign the global post object with the user_login value to trigger the form.
                $_POST['user_login'] = $request->get('login_locker_user_login'); // phpcs:ignore
                $expire = Options::getOption(Settings::GENERAL_SETTING_LP_TIMEOUT, Settings::GENERAL_SETTINGS, 1);
                $this->setLoginCookie($user, sprintf('+%d minute', (int)$expire));
            }
        }
    }

    /**
     * Is the "Allow lost password?" setting enabled?
     * @return bool
     */
    private function isLostPasswordAllowed(): bool
    {
        return filter_var(
            Options::getOption(Settings::GENERAL_SETTING_ALLOW_LP, Settings::GENERAL_SETTINGS),
            FILTER_VALIDATE_BOOL
        );
    }

    /**
     * Is the current URL a lost password or expired request?
     * @return bool
     */
    private function isLostPassOrExpired(): bool
    {
        $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $status = filter_input(INPUT_GET, 'expass', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return $action === 'lostpassword' || $status === 'expired';
    }

    /**
     * Helper to return an array of user data and the flag type used.
     * @param string $input Incoming user credentials, email or login.
     * @return array<string, mixed> An array of user: WP_User object of false, and the field type flag for the
     *     WP_User object data.
     */
    private function getUserBy(string $input): array
    {
        if (is_email($input) !== false) {
            $field = 'email';
            $user = get_user_by($field, sanitize_email($input));
        } else {
            $field = 'login';
            $user = get_user_by($field, sanitize_user($input));
        }

        return ['user' => $user, 'field' => 'user_' . $field];
    }

    /**
     * Validate a cookie created before signed cookies (v2.11.0 and below): `encrypt('OK|{email}')`.
     * Legacy cookies are unauthenticated and never expire server-side, so a valid one is
     * replaced with a signed cookie right away. Disable this transitional path with
     * `add_filter('login_locker/accept_legacy_cookie', '__return_false');`.
     * @param mixed $cookie
     * @return WP_User|null
     */
    private function maybeUpgradeLegacyCookie(mixed $cookie): ?WP_User
    {
        if (
            !is_string($cookie) ||
            $cookie === '' ||
            apply_filters(LoginLocker::HOOK_PREFIX . 'accept_legacy_cookie', true) !== true
        ) {
            return null;
        }

        $value = $this->decrypt($cookie, $this->getEncryptionKey());
        $prefix = sprintf(self::COOKIE_VALUE_S, '');
        if (!is_string($value) || !str_starts_with($value, $prefix)) {
            return null;
        }

        [$user] = array_values($this->getUserBy(substr($value, strlen($prefix))));
        if (!$user instanceof WP_User) {
            return null;
        }

        $this->setLoginCookie($user);

        return $user;
    }

    /**
     * Gets the sites encryption key. This value is dynamically set into the DB.
     * @return string a 32 character encryption key.
     */
    private function getEncryptionKey(): string
    {
        $key = self::hashEncryptionKey();
        [$prefix] = explode(self::ENCRYPTION_DELIMITER, $key);

        return sprintf('%s%s%s', $prefix, substr($key, 0, -(strlen($prefix) + 1)), self::ENCRYPTION_DELIMITER);
    }

    /**
     * Set's the user login cookie: a signed token that expires server-side, too.
     * @param WP_User $user WP_User object.
     * @param string|null $expire Cookie expiration time.
     */
    private function setLoginCookie(WP_User $user, ?string $expire = null): void
    {
        /**
         * Dev note, you can't use Symfony's Response()->headers->setCookie( new Cookie( 'name', 'value' ) )
         * because it sends the headers, which then makes the login page throw a message notice
         * that the headers have already been sent. So, default `setcookie` is used.
         */
        $expires = strtotime($expire ?? self::getCookieExpire());
        if ($expires === false) {
            $expires = strtotime(sprintf(self::COOKIE_EXPIRE_S, Settings::COOKIE_EXPIRE_DAYS_DEFAULT));
        }
        if (!headers_sent()) {
            setcookie(
                self::COOKIE_NAME,
                SignedToken::create($user, self::COOKIE_NAME, $expires),
                [
                    'expires' => $expires,
                    'path' => COOKIEPATH,
                    'domain' => is_string(COOKIE_DOMAIN) ? COOKIE_DOMAIN : parse_url(home_url(), PHP_URL_HOST),
                    'secure' => is_ssl() && parse_url(get_option('home'), PHP_URL_SCHEME) === 'https',
                    'httponly' => true,
                ]
            );
        }
    }

    /**
     * Activation method to add the metadata to the current user.
     */
    private function addDefaultUserMeta(): void
    {
        NewUser::addLoginUserMeta(get_current_user_id(), $this->getIp());
    }
}
