<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Utilities;

use TheFrosty\WpLoginLocker\LoginLocker;

/**
 * Class UserMetaCleanup
 * @package TheFrosty\WpLoginLocker\Utilities
 */
class UserMetaCleanup
{

    /**
     * User ID.
     *
     * @var int $user_id
     */
    private $user_id;

    private const MAX_POST_META_COUNT = 10;

    /**
     * UserMetaCleanup constructor.
     *
     * @param int $user_id
     */
    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * Initiated the user meta cleaning.
     */
    public function cleanup(): void
    {
        $this->cleanupIp();
        $this->cleanupTime();
    }

    /**
     * Clean old IP meta.
     */
    private function cleanupIp(): void
    {
        $meta_ids = $this->query(LoginLocker::LAST_LOGIN_IP_META_KEY);

        if (!\count($meta_ids) || \count($meta_ids) > self::MAX_POST_META_COUNT + 5) {
            return;
        }

        $this->delete(\array_diff($meta_ids, $this->getSlice($meta_ids)));
    }

    /**
     * Clean old time meta.
     */
    private function cleanupTime(): void
    {
        $meta_ids = $this->query(LoginLocker::LAST_LOGIN_TIME_META_KEY);

        if (!\count($meta_ids) || \count($meta_ids) > self::MAX_POST_META_COUNT + 5) {
            return;
        }

        $this->delete(\array_diff($meta_ids, $this->getSlice($meta_ids)));
    }

    /**
     * Get the last X of the meta_id array.
     * @param array $meta_ids
     * @return array
     */
    private function getSlice(array $meta_ids): array
    {
        return \array_slice($meta_ids, -self::MAX_POST_META_COUNT, self::MAX_POST_META_COUNT, true);
    }

    /**
     * Get the meta array from users.
     * @param string $meta_key
     * @return array
     */
    private function query(string $meta_key): array
    {
        global $wpdb;
        $table = \_get_meta_table('user');
        $query = $wpdb->prepare(
            "SELECT umeta_id FROM $table WHERE user_id = %d AND meta_key = %s",
            $this->user_id,
            $meta_key
        );

        return $wpdb->get_col($query);
    }

    /**
     * Delete the arrays past down from the diff.
     *
     * @param array $meta_ids
     */
    private function delete(array $meta_ids): void
    {
        global $wpdb;
        $table = \_get_meta_table('user');
        $query = "DELETE FROM $table WHERE umeta_id IN( " . \implode(',', $meta_ids) . ' )';

        $wpdb->query($query);
    }
}
