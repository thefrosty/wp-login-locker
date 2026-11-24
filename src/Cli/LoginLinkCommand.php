<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Cli;

use TheFrosty\WpLoginLocker\Login\LoginLink;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpUtilities\Plugin\PluginAwareInterface;
use TheFrosty\WpUtilities\Plugin\PluginAwareTrait;
use WP_CLI;
use WP_User;
use function ctype_digit;
use function get_option;
use function get_user_by;
use function sprintf;
use function WP_CLI\Utils\get_flag_value;
use function wp_date;

/**
 * Class LoginLinkCommand
 * `wp login-locker link <user>`: recover access to wp-login.php when email isn't an option.
 * A thin WP-CLI wrapper around LoginLink and WpLogin (which are tested); WP-CLI isn't bootstrapped in PHPUnit.
 * @codeCoverageIgnore
 * @package TheFrosty\WpLoginLocker\Cli
 */
final class LoginLinkCommand implements PluginAwareInterface
{

    use PluginAwareTrait;

    public const string COMMAND = 'login-locker link';
    public const string FLAG_PORCELAIN = 'porcelain';
    public const string FLAG_SEND = 'send';

    /**
     * Get a signed login link that unlocks wp-login.php for a user.
     *
     * ## OPTIONS
     *
     * <user>
     * : The user ID, username or email address.
     *
     * [--send]
     * : Email the link to the user instead of printing it.
     *
     * [--porcelain]
     * : Only output the link.
     *
     * ## EXAMPLES
     *
     *     # Print a login link for the "admin" user.
     *     $ wp login-locker link admin
     *
     *     # Email a login link to the user.
     *     $ wp login-locker link admin@example.com --send
     *
     * @param array<int, string> $args
     * @param array<string, mixed> $assoc_args
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        $login = $args[0] ?? '';
        $user = ctype_digit($login) ? get_user_by('id', (int)$login) : LoginLink::findUser($login);
        if (!$user instanceof WP_User) {
            WP_CLI::error(sprintf('User "%s" not found.', $login));
        }

        if (get_flag_value($assoc_args, self::FLAG_SEND, false)) {
            $login_link = new LoginLink();
            $login_link->setPlugin($this->getPlugin());
            if (!$login_link->send($user)) {
                WP_CLI::error(sprintf('Could not email the login link to %s.', $user->user_email));
            }
            WP_CLI::success(sprintf('Login link emailed to %s.', $user->user_email));

            return;
        }

        $expires = LoginLink::getExpires($user);
        WP_CLI::line(WpLogin::getAuthCheckUrl($user, $expires));
        if (!get_flag_value($assoc_args, self::FLAG_PORCELAIN, false)) {
            WP_CLI::success(
                sprintf(
                    'Login link for %s, valid until %s.',
                    $user->user_login,
                    (string)wp_date(get_option('date_format') . ' ' . get_option('time_format'), $expires)
                )
            );
        }
    }
}
