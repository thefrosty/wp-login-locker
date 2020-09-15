<?php
function_exists('login_header') || wp_die();
login_header(__('Log In'), '', '');
?>
<p class="message"><?php
    echo apply_filters(
        TheFrosty\WpLoginLocker\LoginLocker::HOOK_PREFIX . 'wp-login/message',
        esc_html__('Login without a key has been disabled.', 'wp-login-locker')
    );
    ?></p>
</div>
<div class="clear"></div>
</body>
</html>
