<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of TokenTest
 *
 * @group HeaderParts
 * @group Token
 * @covers ZBateson\MailMimeParser\Header\Part\Token
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class TokenTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;

    protected function setUp() : void
    {
        $this->mb = new MbWrapper();
    }

    private function newToken($value, $isLiteral = false)
    {
        return new Token(\mmpGetTestLogger(), $this->mb, $value, $isLiteral);
    }

    public function testInstance() : void
    {
        $token = $this->newToken('testing');
        $this->assertNotNull($token);
        $this->assertEquals('testing', $token->getValue());
        $this->assertEquals('testing', (string) $token);
    }
}
