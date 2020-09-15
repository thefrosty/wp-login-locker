<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Helpers;

/**
 * Close the current session and terminate all scripts.
 */
function terminate(): void
{
    \session_write_close();
    \wp_die();
}
