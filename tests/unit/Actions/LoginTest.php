<?php declare(strict_types=1);

namespace TheFrosty\Tests\WpLoginLocker\Actions;

use PHPUnit\Framework\TestCase;
use TheFrosty\WpLoginLocker\Actions\Login;

/**
 * Class Login
 * @package TheFrosty\Tests\WpLoginLocker\Actions
 */
class LoginTest extends TestCase
{

    /**
     * @var Login $login
     */
    private $login;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->login = new Login();
    }

    public function tearDown()
    {
        unset($this->login);
    }
}
