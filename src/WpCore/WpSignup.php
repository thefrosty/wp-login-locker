<?php declare(strict_types=1);

namespace Dwnload\WpLoginLocker\WpCore;

use function Dwnload\WpLoginLocker\Helpers\terminate;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;

/**
 * Class WpSignup
 *
 * @package Dwnload\WpLoginLocker\WpCore
 */
class WpSignup extends AbstractHookProvider implements HttpFoundationRequestInterface, WpHooksInterface
{

    use HttpFoundationRequestTrait, HooksTrait;

    /**
     * Add class hooks.
     */
    public function addHooks()
    {
        $this->addAction('before_signup_header', [$this, 'redirectWpSignup']);
    }

    /**
     * Redirect all requests to the 'wp-signup.php' page back to the network home URL.
     */
    protected function redirectWpSignup()
    {
        // Don't allow POST requests to the wp-signup.php page
        if (!empty($this->getRequest()->request->all())) {
            \wp_die(\esc_html__('Ah ah ah, you didn\'t say the magic word.', 'wp-login-locker'), 'Access Denied');
        }
        \wp_safe_redirect(\network_home_url(), Response::HTTP_PERMANENTLY_REDIRECT);
        terminate();
    }
}
