<?php declare(strict_types=1); // @phpcs:disable

use Dwnload\WpSettingsApi\Api\Options;
use TheFrosty\WpLoginLocker\Settings\Settings;

$background_color = Options::getOption(
    Settings::EMAIL_SETTING_BACKGROUND_COLOR,
    Settings::EMAIL_SETTINGS,
    Settings::BACKGROUND_COLOR_DEFAULT
);
$full_bleed = Options::getOption(
    Settings::EMAIL_SETTING_FULL_BLEED_COLOR,
    Settings::EMAIL_SETTINGS,
    Settings::FULL_BLEED_COLOR_DEFAULT
);
$header_image = Options::getOption(
    Settings::EMAIL_SETTING_HEADER_IMAGE,
    Settings::EMAIL_SETTINGS,
    null
);
$hero_image = Options::getOption(
    Settings::EMAIL_SETTING_HERO_IMAGE,
    Settings::EMAIL_SETTINGS,
    null
);
?>
<!--
	The email background color (#222222) is defined in three places:
	1. body tag: for most email clients
	2. center tag: for Gmail and Inbox mobile apps and web versions of Gmail, GSuite, Inbox, Yahoo, AOL, Libero, Comcast, freenet, Mail.ru, Orange.fr
	3. mso conditional: For Windows 10 Mail
-->
<body width="100%"
      style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: <?php echo \sanitize_hex_color($background_color); ?>;">
<center style="width: 100%; background-color: <?php echo \sanitize_hex_color($background_color) ?>;">
    <!--[if mso | IE]>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: <?php echo \sanitize_hex_color($background_color) ?>;">
        <tr>
            <td>
    <![endif]-->

    <!-- Visually Hidden Preheader Text : BEGIN -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
        {pretext}
    </div>
    <!-- Visually Hidden Preheader Text : END -->

    <!-- Create white space after the desired preview text so email clients don’t pull other distracting text into the inbox preview. Extend as necessary. -->
    <!-- Preview Text Spacing Hack : BEGIN -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
        &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
    </div>
    <!-- Preview Text Spacing Hack : END -->

    <!--
        Set the email width. Defined in two places:
        1. max-width for all clients except Desktop Windows Outlook, allowing the email to squish on narrow but never go wider than 600px.
        2. MSO tags for Desktop Windows Outlook enforce a 600px width.
    -->
    <div style="max-width: 600px; margin: 0 auto;" class="email-container">
        <!--[if mso]>
        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600">
            <tr>
                <td>
        <![endif]-->

        <!-- Email Body : BEGIN -->
        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
               style="margin: 0 auto;">
            <?php if (!empty($header_image)) : ?>
            <!-- Email Header : BEGIN -->
            <tr>
                <td style="padding: 20px 0; text-align: center">
                    <img src="<?php echo esc_url($header_image); ?>" width="200" height="50" alt="" border="0"
                         style="height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
                </td>
            </tr>
            <!-- Email Header : END -->
            <?php endif; ?>

            <?php if (!empty($hero_image)) : ?>
            <!-- Hero Image, Flush : BEGIN -->
            <tr>
                <td style="background-color: #ffffff;">
                    <img src="<?php echo esc_url($hero_image); ?>>" width="600" height="" alt="" border="0"
                         style="width: 100%; max-width: 600px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555; margin: auto;"
                         class="g-img">
                </td>
            </tr>
            <!-- Hero Image, Flush : END -->
            <?php endif; ?>

            <!-- 1 Column Text : BEGIN -->
            <tr>
                <td style="background-color: #ffffff;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <td style="padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                <p style="margin: 0;">{message}</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- 1 Column Text : END -->

            <!-- Clear Spacer : BEGIN -->
            <tr>
                <td aria-hidden="true" height="40" style="font-size: 0px; line-height: 0px;">
                    &nbsp;
                </td>
            </tr>
            <!-- Clear Spacer : END -->

        </table>
        <!-- Email Body : END -->

        <!--[if mso]>
        </td>
        </tr>
        </table>
        <![endif]-->
    </div>

    <!-- Full Bleed Background Section : BEGIN -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
           style="background-color: <?php echo \sanitize_hex_color($full_bleed) ?>;">
        <tr>
            <td valign="top">
                <div style="max-width: 600px; margin: auto;" class="email-container">
                    <!--[if mso]>
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" align="center">
                        <tr>
                            <td>
                    <![endif]-->
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <td style="padding: 20px; text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #ffffff;">
                                <p style="margin: 0;">{pretext}</p>
                            </td>
                        </tr>
                    </table>
                    <!--[if mso]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                </div>
            </td>
        </tr>
    </table>
    <!-- Full Bleed Background Section : END -->

    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <![endif]-->
</center>
