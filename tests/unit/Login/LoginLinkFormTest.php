<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Login;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Actions\NewUser;
use TheFrosty\WpLoginLocker\Login\LoginLink;
use TheFrosty\WpLoginLocker\Login\WpLogin;
use TheFrosty\WpLoginLocker\Utilities\GeoUtilTrait;
use TheFrosty\WpLoginLocker\Utilities\SignedToken;
use TheFrosty\WpLoginLocker\WpMail\WpMail;
use WP_User;
use function array_unique;
use function md5;
use function time;
use function wp_using_ext_object_cache;

/**
 * Class LoginLinkFormTest
 * End-to-end checks of the "Email me a login link" form handler (WpLogin::maybeRequestLoginLink()):
 * its rate limits, that client IP headers can't be used to get around them, and that every outcome
 * looks the same to the requester.
 * @package TheFrosty\Tests\WpLoginLocker\Login
 */
#[CoversClass(WpLogin::class)]
#[Group('login')]
#[UsesClass(LoginLink::class)]
#[UsesClass(NewUser::class)]
#[UsesClass(SignedToken::class)]
#[UsesClass(WpMail::class)]
#[UsesTrait(GeoUtilTrait::class)]
class LoginLinkFormTest extends TestCase
{

    private const string IP = '198.51.100.20';
    private const string OTHER_IP = '198.51.100.21';

    private WpLogin $wpLogin;

    private ReflectionMethod $maybeRequestLoginLink;

    private ?Request $originalRequest;

    /** @var array<int, array> */
    private array $mails = [];

    /** @var string[] */
    private array $ips = [self::IP, self::OTHER_IP];

    /** @var int[] */
    private array $userIds = [];

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->wpLogin = new WpLogin();
        $this->wpLogin->setPlugin($this->plugin);
        $this->wpLogin->setRequest(Request::createFromGlobals());
        $this->reflection = $this->getReflection($this->wpLogin);
        $this->maybeRequestLoginLink = $this->reflection->getMethod('maybeRequestLoginLink');
        $this->originalRequest = $this->reflection->getProperty('request')->getValue();
        $this->mails = [];
        $this->userIds = [];
        \add_filter('pre_wp_mail', [$this, 'captureMail'], 10, 2);
    }

    #[Override]
    public function tearDown(): void
    {
        \remove_filter('pre_wp_mail', [$this, 'captureMail']);
        foreach (array_unique($this->ips) as $ip) {
            \delete_transient(LoginLink::TRANSIENT_IP_PREFIX . md5($ip));
        }
        foreach ($this->userIds as $user_id) {
            \delete_transient(LoginLink::TRANSIENT_USER_PREFIX . $user_id);
        }
        $this->reflection->getProperty('request')->setValue(null, $this->originalRequest);
        unset($this->wpLogin);
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

    public function testPerUserLimit(): void
    {
        $user = $this->createUser();
        for ($i = 0; $i < 3; $i++) {
            $this->submit($user->user_email);
        }
        $this->assertCount(1, $this->mails, 'Only one email per user per USER_WINDOW');
    }

    public function testPerIpLimit(): void
    {
        for ($i = 0; $i < LoginLink::IP_LIMIT + 2; $i++) {
            $this->submit($this->createUser()->user_login);
        }
        $this->assertCount(LoginLink::IP_LIMIT, $this->mails, 'Only IP_LIMIT requests per IP per IP_WINDOW');
    }

    public function testSpoofedIpHeadersDontBypassTheIpLimit(): void
    {
        for ($i = 0; $i < LoginLink::IP_LIMIT + 2; $i++) {
            // A different "client IP" in every header the attacker controls; REMOTE_ADDR stays the same.
            $spoofed = "203.0.113.{$i}";
            $this->ips[] = $spoofed;
            $this->submit($this->createUser()->user_login, [
                'HTTP_CLIENT_IP' => $spoofed,
                'HTTP_X_FORWARDED_FOR' => $spoofed,
                'HTTP_CF_CONNECTING_IP' => $spoofed,
                'HTTP_FORWARDED' => "for={$spoofed}",
            ]);
        }
        $this->assertCount(LoginLink::IP_LIMIT, $this->mails);
    }

    public function testIpsHaveSeparateLimits(): void
    {
        for ($i = 0; $i < LoginLink::IP_LIMIT; $i++) {
            $this->submit($this->createUser()->user_login);
        }
        $this->submit($this->createUser()->user_login);
        $this->assertCount(LoginLink::IP_LIMIT, $this->mails);

        $this->submit($this->createUser()->user_login, ['REMOTE_ADDR' => self::OTHER_IP]);
        $this->assertCount(LoginLink::IP_LIMIT + 1, $this->mails);
    }

    public function testUnknownAccountsCountAgainstTheIpLimit(): void
    {
        for ($i = 0; $i < LoginLink::IP_LIMIT; $i++) {
            $this->submit("nobody-{$i}@email.test");
        }
        $this->submit($this->createUser()->user_email);
        $this->assertCount(0, $this->mails);
    }

    public function testEveryOutcomeLooksTheSame(): void
    {
        $user = $this->createUser();
        $responses = [
            'sent' => $this->submit($user->user_email),
            'user rate limited' => $this->submit($user->user_email),
            'unknown account' => $this->submit('nobody@email.test'),
            'empty' => $this->submit(''),
            'array input' => $this->submit(['not', 'a', 'string']),
        ];
        for ($i = 0; $i < LoginLink::IP_LIMIT; $i++) {
            $responses["ip rate limited {$i}"] = $this->submit('nobody@email.test');
        }

        $this->assertCount(1, $this->mails);
        $this->assertNotNull($responses['sent']);
        $this->assertCount(1, array_unique($responses), 'The response must not reveal the outcome');
    }

    public function testOnlyPostRequestsWithTheRequestKeyAreHandled(): void
    {
        $user = $this->createUser();
        $this->setRequest(Request::create(\wp_login_url(), Request::METHOD_POST, [
            LoginLink::REQUEST_FIELD => $user->user_email,
        ]));
        $this->assertNull($this->maybeRequestLoginLink->invoke($this->wpLogin));

        $this->setRequest(Request::create(\add_query_arg([
            LoginLink::REQUEST_KEY => true,
            LoginLink::REQUEST_FIELD => $user->user_email,
        ], \wp_login_url())));
        $this->assertNull($this->maybeRequestLoginLink->invoke($this->wpLogin));
        $this->assertCount(0, $this->mails);
    }

    public function testLimitsExpireAfterTheirWindows(): void
    {
        if (wp_using_ext_object_cache()) {
            $this->markTestSkipped('Transient timeouts are only stored as options without an object cache.');
        }

        $user = $this->createUser();
        $this->submit($user->user_email);
        $ip_key = LoginLink::TRANSIENT_IP_PREFIX . md5(self::IP);
        $user_key = LoginLink::TRANSIENT_USER_PREFIX . $user->ID;
        $this->assertEqualsWithDelta(time() + LoginLink::IP_WINDOW, (int)\get_option("_transient_timeout_{$ip_key}"), 2);
        $this->assertEqualsWithDelta(
            time() + LoginLink::USER_WINDOW,
            (int)\get_option("_transient_timeout_{$user_key}"),
            2
        );

        // Once the user window has passed, the user can get another email.
        $this->submit($user->user_email);
        $this->assertCount(1, $this->mails);
        \update_option("_transient_timeout_{$user_key}", time() - 1);
        $this->submit($user->user_email);
        $this->assertCount(2, $this->mails);
    }

    /**
     * Submit the "Email me a login link" form.
     * @param mixed $login
     * @param array<string, string> $server
     * @return string|null The message shown to the requester.
     */
    private function submit(mixed $login, array $server = []): ?string
    {
        $this->setRequest(
            Request::create(
                \add_query_arg(LoginLink::REQUEST_KEY, true, \wp_login_url()),
                Request::METHOD_POST,
                [LoginLink::REQUEST_FIELD => $login],
                server: $server + ['REMOTE_ADDR' => self::IP]
            )
        );

        return $this->maybeRequestLoginLink->invoke($this->wpLogin);
    }

    /**
     * HttpFoundationRequestTrait::setRequest() only sets the (static) request once.
     * @param Request $request
     */
    private function setRequest(Request $request): void
    {
        $this->reflection->getProperty('request')->setValue(null, $request);
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
}
