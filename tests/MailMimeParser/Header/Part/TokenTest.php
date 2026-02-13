<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of TokenTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(Token::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('Token')]
class TokenTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;

    protected function setUp() : void
    {
        $this->mb = new MbWrapper();
    }

    private function newToken($value, $isLiteral = false, $preserveSpaces = false)
    {
        return new Token(\mmpGetTestLogger(), $this->mb, $value, $isLiteral, $preserveSpaces);
    }

    public function testInstance() : void
    {
        $token = $this->newToken('testing');
        $this->assertNotNull($token);
        $this->assertEquals('testing', $token->getValue());
        $this->assertEquals('testing', (string) $token);
    }

    public function testNewLines() : void
    {
        $token = $this->newToken("tes\n\tting");
        $this->assertNotNull($token);
        $this->assertEquals("tes\tting", $token->getValue());
    }

    public function testNewLinesTabAndSpace() : void
    {
        $token = $this->newToken("tes\n\t   ting");
        $this->assertNotNull($token);
        $this->assertEquals("tes\t   ting", $token->getValue());
    }

    public function testLiteralAndPreserveSpace() : void
    {
        $token = $this->newToken('   ');
        $this->assertNotNull($token);
        $this->assertEquals(' ', $token->getValue());

        $token = $this->newToken("\n   ", false, true);
        $this->assertNotNull($token);
        $this->assertEquals('   ', $token->getValue());

        $token = $this->newToken("\n   ", true);
        $this->assertNotNull($token);
        $this->assertEquals("\n   ", $token->getValue());
    }
}
