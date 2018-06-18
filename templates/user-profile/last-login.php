<?php /** @var \Dwnload\WpLoginLocker\UserProfile\LastLogin $this */ ?>
<h4><?php esc_html_e('Your recent login data', 'wp-login-locker'); ?></h4>
<table class="form-table">
    <tr>
        <th scope="row"><?php esc_html_e('Last login IP', 'wp-login-locker'); ?></th>
        <td>
            <?php echo esc_html($this->getLastLoginIp($user->ID)); ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Last login Date', 'wp-login-locker'); ?></th>
        <td>
            <?php echo esc_html($this->getLastLogin($user->ID)); ?>
        </td>
    </tr>
</table>
