<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Utilities;

use WP_User;
use function count;
use function ctype_digit;
use function explode;
use function get_user_by;
use function hash_equals;
use function hash_hmac;
use function implode;
use function is_string;
use function substr;
use function time;
use function wp_salt;

/**
 * Class SignedToken
 * Creates and verifies HMAC-signed, expiring tokens that are bound to a user.
 * Tokens are signed with the site's `auth` salt (wp-config.php), so they can't be forged
 * without server-side secrets, and they are invalidated when the user's email or password changes.
 * @package TheFrosty\WpLoginLocker\Utilities
 */
final class SignedToken
{

    private const string DELIMITER = '.';

    /**
     * Create a token for a user.
     * @param WP_User $user The user the token is bound to.
     * @param string $purpose A context string, so a token for one purpose can't be used for another.
     * @param int $expires Unix timestamp the token expires at.
     * @return string
     */
    public static function create(WP_User $user, string $purpose, int $expires): string
    {
        return implode(
            self::DELIMITER,
            [$user->ID, $expires, self::sign($user, $purpose, $expires)]
        );
    }

    /**
     * Verify a token and return the user it belongs to.
     * @param mixed $token The incoming token.
     * @param string $purpose The context string the token must have been created with.
     * @return WP_User|null The user on success, null when the token is invalid or expired.
     */
    public static function verify(mixed $token, string $purpose): ?WP_User
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        $parts = explode(self::DELIMITER, $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$user_id, $expires, $signature] = $parts;
        if (!ctype_digit($user_id) || !ctype_digit($expires) || (int)$expires < time()) {
            return null;
        }

        $user = get_user_by('id', (int)$user_id);
        if (!$user instanceof WP_User) {
            return null;
        }

        return hash_equals(self::sign($user, $purpose, (int)$expires), $signature) ? $user : null;
    }

    /**
     * Build the HMAC signature.
     * @param WP_User $user
     * @param string $purpose
     * @param int $expires
     * @return string
     */
    private static function sign(WP_User $user, string $purpose, int $expires): string
    {
        // Bind to the email and a fragment of the password hash (like core's auth cookie) so
        // changing either one invalidates every outstanding token for this user.
        $data = implode('|', [$purpose, $user->ID, $expires, $user->user_email, substr($user->user_pass, 8, 4)]);

        return hash_hmac('sha256', $data, wp_salt('auth'));
    }
}
