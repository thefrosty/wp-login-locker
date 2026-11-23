<?php

// phpcs:disable Generic.Files.LineLength.TooLong -- Wrapping would change the email text.
esc_html_e('Hi %1$s,

Here is your link to log in to %2$s:

%3$s

It works until %4$s. If you didn\'t ask for it, you can ignore this email; nothing on your account has changed.

Requested from IP address: %5$s

Thanks,

%2$s', 'wp-login-locker');
