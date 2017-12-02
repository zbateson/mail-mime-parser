<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of TokenTest
 *
 * @group HeaderParts
 * @group Token
 * @covers ZBateson\MailMimeParser\Header\Part\Token
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class TokenTest extends PHPUnit_Framework_TestCase
{
    private $charsetConverter;
    
    public function setUp()
    {
        $this->charsetConverter = $this->getMock('ZBateson\MailMimeParser\Util\CharsetConverter');
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
