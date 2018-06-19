<?php /** @var \Dwnload\WpLoginLocker\UserProfile\LastLogin $this */ ?>
<h4><?php esc_html_e('Your recent login data', 'wp-login-locker'); ?></h4>
<table class="form-table">
    <tr>
        <th scope="row"><?php esc_html_e('Previous IP', 'wp-login-locker'); ?></th>
        <td>
            <?php echo esc_html($this->getLastLoginIp($user->ID)); ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Current IP', 'wp-login-locker'); ?></th>
        <td>
            <?php echo esc_html($this->getCurrentLoginIp($user->ID)); ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Previous Login', 'wp-login-locker'); ?></th>
        <td>
            <?php echo esc_html($this->getLastLogin($user->ID)); ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Current Login', 'wp-login-locker'); ?></th>
        <td>
            <?php echo esc_html($this->getCurrentLogin($user->ID)); ?>,
        </td>
    </tr>
</table>
