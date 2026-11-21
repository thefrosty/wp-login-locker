<?php

declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Utilities;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use TheFrosty\Tests\WpLoginLocker\TestCase;
use TheFrosty\WpLoginLocker\Utilities\SignedToken;
use WP_User;
use function time;

/**
 * Class SignedTokenTest
 * @package TheFrosty\Tests\WpLoginLocker\Utilities
 */
#[CoversClass(SignedToken::class)]
#[Group('utilities')]
class SignedTokenTest extends TestCase
{

    public function testCreateAndVerify(): void
    {
        $user = self::factory()->user->create_and_get();
        $token = SignedToken::create($user, 'test', time() + 60);
        $actual = SignedToken::verify($token, 'test');
        $this->assertInstanceOf(WP_User::class, $actual);
        $this->assertSame($user->ID, $actual->ID);
    }

    public function testVerifyRejectsWrongPurpose(): void
    {
        $user = self::factory()->user->create_and_get();
        $this->assertNull(SignedToken::verify(SignedToken::create($user, 'a', time() + 60), 'b'));
    }

    public function testVerifyRejectsExpired(): void
    {
        $user = self::factory()->user->create_and_get();
        $this->assertNull(SignedToken::verify(SignedToken::create($user, 'test', time() - 1), 'test'));
    }

    public function testVerifyRejectsTampering(): void
    {
        $user = self::factory()->user->create_and_get();
        $other = self::factory()->user->create_and_get();
        $token = SignedToken::create($user, 'test', time() + 60);
        [, $expires, $signature] = \explode('.', $token);
        $this->assertNull(SignedToken::verify("{$other->ID}.{$expires}.{$signature}", 'test'));
        $this->assertNull(SignedToken::verify("{$user->ID}." . ($expires + 3600) . ".{$signature}", 'test'));
        $this->assertNull(SignedToken::verify($user->user_login, 'test'));
        $this->assertNull(SignedToken::verify(['array'], 'test'));
        $this->assertNull(SignedToken::verify('', 'test'));
    }

    public function testVerifyRejectsAfterEmailChange(): void
    {
        $user = self::factory()->user->create_and_get();
        $token = SignedToken::create($user, 'test', time() + 60);
        \wp_update_user(['ID' => $user->ID, 'user_email' => 'changed@email.test']);
        \clean_user_cache($user->ID);
        $this->assertNull(SignedToken::verify($token, 'test'));
    }
}
