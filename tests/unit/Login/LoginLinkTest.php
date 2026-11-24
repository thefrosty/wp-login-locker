<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Login;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\Login\LoginLink;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpLoginLocker\Utilities\SignedToken;
use TheFrosty\WpLoginLocker\WpMail\WpMail;
use WP_User;
use function md5;
use function preg_match;
use function str_repeat;
use const HOUR_IN_SECONDS;
use const MINUTE_IN_SECONDS;

/**
 * Class LoginLinkTest
 * @package TheFrosty\Tests\WpLoginLocker\Login
 */
#[CoversClass(LoginLink::class)]
#[Group('login')]
#[UsesClass(NewUser::class)]
#[UsesClass(SignedToken::class)]
#[UsesClass(WpLogin::class)]
#[UsesClass(WpMail::class)]
#[UsesTrait(GeoUtilTrait::class)]
class LoginLinkTest extends TestCase
{

    private const string IP = '203.0.113.7';

    private LoginLink $loginLink;

    /** @var array<int, array{to: string|string[], subject: string, message: string}> */
    private array $mails = [];

    /** @var int[] */
    private array $userIds = [];

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->loginLink = new LoginLink();
        $this->loginLink->setPlugin($this->plugin);
        $this->mails = [];
        $this->userIds = [];
        \add_filter('pre_wp_mail', [$this, 'captureMail'], 10, 2);
    }

    #[Override]
    public function tearDown(): void
    {
        \remove_filter('pre_wp_mail', [$this, 'captureMail']);
        \delete_transient(LoginLink::TRANSIENT_IP_PREFIX . md5(self::IP));
        \delete_transient(LoginLink::TRANSIENT_IP_PREFIX . md5(''));
        foreach ($this->userIds as $user_id) {
            \delete_transient(LoginLink::TRANSIENT_USER_PREFIX . $user_id);
        }
        unset($this->loginLink);
        parent::tearDown();
    }

    /**
     * Capture (and short-circuit) outgoing mail.
     * @param null|bool $return
     * @param array $atts
     * @return bool
     */
    public function captureMail(?bool $return, array $atts): bool
    {
        $this->mails[] = $atts;

        return true;
    }

    public function testFindUser(): void
    {
        $user = $this->createUser();
        $this->assertSame($user->ID, LoginLink::findUser($user->user_login)?->ID);
        $this->assertSame($user->ID, LoginLink::findUser($user->user_email)?->ID);
        $this->assertNull(LoginLink::findUser(''));
        $this->assertNull(LoginLink::findUser('no-such-user'));
        $this->assertNull(LoginLink::findUser('nobody@email.test'));
    }

    public function testSendEmailsAValidLoginLink(): void
    {
        $user = $this->createUser();
        $this->assertTrue($this->loginLink->send($user, self::IP));
        $this->assertCount(1, $this->mails);
        $this->assertSame($user->user_email, $this->mails[0]['to']);
        $this->assertStringContainsString(self::IP, $this->mails[0]['message']);
        // WpMail::textToHtml() makes the login URL a link.
        $this->assertMatchesRegularExpression(
            '/<a href="[^"]*' . WpLogin::AUTH_CHECK_KEY . '=/',
            $this->mails[0]['message']
        );

        $pattern = '/' . WpLogin::AUTH_CHECK_KEY . '=([0-9]+\.[0-9]+\.[a-f0-9]+)/';
        $this->assertSame(1, preg_match($pattern, $this->mails[0]['message'], $matches));
        $this->assertSame($user->ID, SignedToken::verify($matches[1], WpLogin::AUTH_CHECK_KEY)?->ID);
    }

    public function testMagicLinkExpiresInFifteenMinutesByDefault(): void
    {
        $this->assertSame(15 * MINUTE_IN_SECONDS, LoginLink::LINK_TTL);
        $user = $this->createUser();
        $before = \time();
        $this->loginLink->send($user, self::IP);
        $this->assertEqualsWithDelta($before + LoginLink::LINK_TTL, $this->getSentTokenExpires(), 2);
    }

    public function testMagicLinkExpiryIsFilterable(): void
    {
        $user = $this->createUser();
        $ttl = static fn(): int => HOUR_IN_SECONDS;
        \add_filter(LoginLink::FILTER_LINK_TTL, $ttl);
        $this->assertEqualsWithDelta(\time() + HOUR_IN_SECONDS, LoginLink::getExpires($user), 2);
        $this->loginLink->send($user, self::IP);
        $this->assertEqualsWithDelta(\time() + HOUR_IN_SECONDS, $this->getSentTokenExpires(), 2);
        \remove_filter(LoginLink::FILTER_LINK_TTL, $ttl);

        // An invalid TTL falls back to the default.
        \add_filter(LoginLink::FILTER_LINK_TTL, '__return_zero');
        $this->assertEqualsWithDelta(\time() + LoginLink::LINK_TTL, LoginLink::getExpires($user), 2);
        \remove_filter(LoginLink::FILTER_LINK_TTL, '__return_zero');
    }

    public function testRequestUnknownAccountSendsNothing(): void
    {
        $this->assertFalse($this->loginLink->request('no-such-user', self::IP));
        $this->assertFalse($this->loginLink->request('', self::IP));
        $this->assertCount(0, $this->mails);
    }

    public function testRequestIsRateLimitedPerUser(): void
    {
        $user = $this->createUser();
        $this->assertTrue($this->loginLink->request($user->user_login, self::IP));
        $this->assertFalse($this->loginLink->request($user->user_email, null));
        $this->assertCount(1, $this->mails);
    }

    public function testRequestIsRateLimitedPerIp(): void
    {
        for ($i = 0; $i <= LoginLink::IP_LIMIT; $i++) {
            $this->loginLink->request($this->createUser()->user_login, self::IP);
        }
        $this->assertCount(LoginLink::IP_LIMIT, $this->mails);

        // Unknown accounts count against the IP limit too.
        \delete_transient(LoginLink::TRANSIENT_IP_PREFIX . md5(self::IP));
        for ($i = 0; $i < LoginLink::IP_LIMIT; $i++) {
            $this->loginLink->request(str_repeat('x', $i + 1), self::IP);
        }
        $this->assertFalse($this->loginLink->request($this->createUser()->user_login, self::IP));
    }

    /**
     * @return WP_User
     */
    private function createUser(): WP_User
    {
        $user = self::factory()->user->create_and_get();
        $this->userIds[] = $user->ID;

        return $user;
    }

    /**
     * Get the expiry from the signed token in the last sent email.
     * @return int
     */
    private function getSentTokenExpires(): int
    {
        $message = \end($this->mails)['message'];
        $pattern = '/' . WpLogin::AUTH_CHECK_KEY . '=([0-9]+)\.([0-9]+)\.[a-f0-9]+/';
        $this->assertSame(1, preg_match($pattern, $message, $matches));

        return (int)$matches[2];
    }
}
