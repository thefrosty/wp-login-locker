<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Helpers;

use JetBrains\PhpStorm\NoReturn;

/**
 * Close the current session and terminate all scripts.
 */
#[NoReturn]
function terminate(): void
{
    \session_write_close();
    \wp_die();
}
