<?php
namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\TestCase;

/**
 * Description of MailMimeParserTest
 *
 * @group MailMimeParser
 * @group Base
 * @covers ZBateson\MailMimeParser\MailMimeParser
 * @author Zaahid Bateson
 */
class MailMimeParserTest extends TestCase
{
    private $mockDi;
    private $mmp;
    
    protected function setUp()
    {
        $this->mockDi = $this->getMockBuilder('ZBateson\MailMimeParser\Container')
            ->disableOriginalConstructor()
            ->setMethods(['newMessageParser'])
            ->getMock();
        $this->mmp = new MailMimeParser($this->mockDi);
    }
    
    public function testConstructMailMimeParser()
    {
        $this->assertNotNull($this->mmp);
    }

    public function testParseFromHandle()
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, 'This is a test');
        rewind($handle);

        $mockParser = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MessageParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDi
            ->expects($this->once())
            ->method('newMessageParser')
            ->willReturn($mockParser);
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        $ret = $this->mmp->parse($handle);
        $this->assertEquals('test', $ret);
    }

    public function testParseFromString()
    {
        $mockParser = $this->getMockBuilder('ZBateson\MailMimeParser\Message\MessageParser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDi
            ->expects($this->once())
            ->method('newMessageParser')
            ->willReturn($mockParser);
        $mockParser
            ->expects($this->once())
            ->method('parse')
            ->willReturn('test');

        $ret = $this->mmp->parse('This is a test');
        $this->assertEquals('test', $ret);
    }
}
