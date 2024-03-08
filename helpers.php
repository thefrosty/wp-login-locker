<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Helpers;

use function session_write_close;
use function wp_die;

/**
 * Close the current session and terminate all scripts.
 */
function terminate(): never
{
    session_write_close();
    wp_die();
}
