<?php declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\Helpers;

/**
 * Close the current session and terminate all scripts.
 */
function terminate(): void
{
//    $function = \apply_filters('thefrosty/wp-login-locker/exit_handler', 'exit');
//    \call_user_func($function);
    \session_write_close();
    exit;
}
