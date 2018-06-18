<?php

use Dwnload\WpLoginLocker\Login\LastLoginColumns;

$user_login_ip = get_user_meta($user->ID, LastLoginColumns::LAST_LOGIN_IP_META_KEY, false);
$user_login_time = get_user_meta($user->ID, LastLoginColumns::LAST_LOGIN_TIME_META_KEY, false);
if (empty($user_login_ip) || empty($user_login_time)) {
    return;
}
?>

<h3><?php esc_html_e('Last login data', 'wp-login-locker'); ?></h3>

<table class="form-table">

    <tr>
        <th><?php esc_html_e('Last login IP', 'wp-login-locker'); ?></th>

        <td>
            <?php echo '<strong>' . esc_html(end($user_login_ip)) . '</strong>'; ?>
        </td>

        <th><?php esc_html_e('Last login Date', 'wp-login-locker'); ?></th>

        <td>
            <?php echo '<strong>' . esc_html(date_i18n(get_option('date_format'),
                    end($user_login_time))) . '</strong>'; ?>
        </td>
    </tr>

</table>
