<?php

declare(strict_types=1);

use TheFrosty\WpLoginLocker\LoginLocker;

function_exists('login_header') || wp_die();
login_header(__('Log In', 'wp-login-locker'), '', '');

$login_url = network_site_url('wp-login.php?action=lostpassword&login_locker=true', 'login_post');
?>
<p class="message"><?php
    echo apply_filters(
        LoginLocker::HOOK_PREFIX . 'wp-login/message',
        esc_html__(
            'Password reset without a key has been disabled. Please enter a valid username or email address.',
            'wp-login-locker'
        )
    );
    ?></p>
<form action="<?php
echo esc_url($login_url); ?>" method="post">
    <p>
        <label for="login_locker_user_login"><?php
            _e('Username or Email Address', 'wp-login-locker'); ?></label>
        <input type="text" name="login_locker_user_login" id="login_locker_user_login" class="input" value="" size="20"
               autocapitalize="off" autocomplete="username" required="required" data-1p-ignore/>
    </p>
    <p class="submit">
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php
        esc_attr_e('Validate', 'wp-login-locker'); ?>"/>
    </p>
</form>
</div>
<div class="clear"></div>
</body>
</html>
