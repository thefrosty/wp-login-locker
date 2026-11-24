<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\WpMail;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\LoginLocker;
use TheFrosty\WpLoginLocker\WpMail\WpMail;

/**
 * Class WpMailTest
 * @package TheFrosty\Tests\WpLoginLocker\WpMail
 */
#[CoversClass(WpMail::class)]
#[Group('wpmail')]
class WpMailTest extends TestCase
{

    private const string URL = 'https://example.org/wp-login.php?reauth=1&auth_check=1.2.abc';

    public function testTextToHtmlMakesUrlsClickable(): void
    {
        $actual = new WpMail()->textToHtml("You can login at:\n\n" . self::URL);
        $this->assertStringContainsString('<p>', $actual);
        $this->assertMatchesRegularExpression('/<a href="[^"]*auth_check=1\.2\.abc"[^>]*>/', $actual);
    }

    public function testTextToHtmlWithoutWpautop(): void
    {
        $filter = LoginLocker::HOOK_PREFIX . 'email_template_wpautop';
        \add_filter($filter, '__return_false');
        $actual = new WpMail()->textToHtml(self::URL);
        \remove_filter($filter, '__return_false');
        $this->assertSame(self::URL, $actual);
    }
}
