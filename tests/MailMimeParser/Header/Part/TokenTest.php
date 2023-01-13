<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
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
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testInstance() : void
    {
        $token = new Token($this->charsetConverter, 'testing');
        $this->assertNotNull($token);
        $this->assertEquals('testing', $token->getValue());
        $this->assertEquals('testing', (string) $token);
    }

    public function testSpaceTokenValue() : void
    {
        $token = new Token($this->charsetConverter, ' ');
        $this->assertTrue($token->ignoreSpacesBefore());
        $this->assertTrue($token->ignoreSpacesAfter());
    }

    public function testNonSpaceTokenValue() : void
    {
        $token = new Token($this->charsetConverter, 'Anything');
        $this->assertFalse($token->ignoreSpacesBefore());
        $this->assertFalse($token->ignoreSpacesAfter());
    }
}
