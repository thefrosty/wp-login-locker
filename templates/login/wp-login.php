<?php

declare(strict_types=1);

use TheFrosty\WpLoginLocker\Login\LoginLink;
use TheFrosty\WpLoginLocker\LoginLocker;

function_exists('login_header') || wp_die();
login_header(__('Log In', 'wp-login-locker'), '', '');

/** @var string|null $message Set by WpLogin::noAuthLoginHtml(). */
?>
<p class="message"><?php
    echo apply_filters(
        LoginLocker::HOOK_PREFIX . 'wp-login/message',
        $message ?? esc_html__('Login without a key has been disabled.', 'wp-login-locker')
    );
    ?></p>
<form action="<?php echo esc_url(add_query_arg(LoginLink::REQUEST_KEY, true, wp_login_url())); ?>" method="post">
    <p>
        <label for="<?php echo esc_attr(LoginLink::REQUEST_FIELD); ?>"><?php
            esc_html_e('Username or Email Address', 'wp-login-locker'); ?></label>
        <input type="text" name="<?php echo esc_attr(LoginLink::REQUEST_FIELD); ?>"
               id="<?php echo esc_attr(LoginLink::REQUEST_FIELD); ?>" class="input" value="" size="20"
               autocapitalize="off" autocomplete="username" required="required"/>
    </p>
    <p class="submit">
        <input type="submit" class="button button-primary button-large" value="<?php
        esc_attr_e('Email me a login link', 'wp-login-locker'); ?>"/>
    </p>
</form>
</div>
<div class="clear"></div>
</body>
</html>
