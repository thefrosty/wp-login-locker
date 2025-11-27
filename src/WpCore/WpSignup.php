<?php

declare(strict_types=1);

namespace TheFrosty\WpLoginLocker\WpCore;

use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use function esc_html__;
use function network_home_url;
use function TheFrosty\WpLoginLocker\Helpers\terminate;
use function TheFrosty\WpUtilities\exitOrThrow;
use function wp_die;
use function wp_safe_redirect;

/**
 * Class WpSignup
 * @package TheFrosty\WpLoginLocker\WpCore
 */
class WpSignup extends AbstractHookProvider implements HttpFoundationRequestInterface
{

    use HttpFoundationRequestTrait;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('before_signup_header', [$this, 'redirectWpSignup']);
    }

    /**
     * Redirect all requests to the 'wp-signup.php' page back to the network home URL.
     * @throws \TheFrosty\WpUtilities\Exceptions\TerminationException
     */
    protected function redirectWpSignup(): never
    {
        // Don't allow POST requests to the wp-signup.php page.
        if (!empty($this->getRequest()->request->all())) {
            wp_die(
                esc_html__('Ah ah ah, you didn\'t say the magic word.', 'wp-login-locker'),
                esc_html__('Access Denied', 'wp-login-locker')
            );
        }
        wp_safe_redirect(network_home_url(), Response::HTTP_PERMANENTLY_REDIRECT);
        exitOrThrow();
    }
}
