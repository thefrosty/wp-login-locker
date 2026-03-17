<?php

declare(strict_types=1);

use TheFrosty\WpLoginLocker\LoginLocker;

function_exists('login_header') || wp_die();
login_header(__('Log In', 'wp-login-locker'), '', '');
?>
<p class="message"><?php
    echo apply_filters(
        LoginLocker::HOOK_PREFIX . 'wp-login/message',
        esc_html__('Login without a key has been disabled.', 'wp-login-locker')
    );
    ?></p>
</div>
<div class="clear"></div>
</body>
</html>
