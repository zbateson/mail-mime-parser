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
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    public function testInstance()
    {
        $token = new Token($this->charsetConverter, 'testing');
        $this->assertNotNull($token);
        $this->assertEquals('testing', $token->getValue());
        $this->assertEquals('testing', strval($token));
    }

    public function testSpaceTokenValue()
    {
        $token = new Token($this->charsetConverter, ' ');
        $this->assertTrue($token->ignoreSpacesBefore());
        $this->assertTrue($token->ignoreSpacesAfter());
    }

    public function testNonSpaceTokenValue()
    {
        $token = new Token($this->charsetConverter, 'Anything');
        $this->assertFalse($token->ignoreSpacesBefore());
        $this->assertFalse($token->ignoreSpacesAfter());
    }
}
