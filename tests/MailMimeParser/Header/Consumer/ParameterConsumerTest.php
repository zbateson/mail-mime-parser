<?php
namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of ParameterConsumerTest
 *
 * @group Consumers
 * @group ParameterConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\ParameterConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
 * @author Zaahid Bateson
 */
class ParameterConsumerTest extends TestCase
{
    private $parameterConsumer;

    protected function setUp(): void
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
			->setMethods(['__toString'])
			->getMock();
        $pf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\HeaderPartFactory')
			->setConstructorArgs([$charsetConverter])
			->setMethods(['__toString'])
			->getMock();
        $mlpf = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory')
			->setConstructorArgs([$charsetConverter])
			->setMethods(['__toString'])
			->getMock();
        $cs = $this->getMockBuilder('ZBateson\MailMimeParser\Header\Consumer\ConsumerService')
			->setConstructorArgs([$pf, $mlpf])
			->setMethods(['__toString'])
			->getMock();
        $this->parameterConsumer = new ParameterConsumer($cs, $pf);
    }

    public function testConsumeTokens()
    {
        $ret = $this->parameterConsumer->__invoke('text/html; charset=utf8');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\LiteralPart', $ret[0]);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\ParameterPart', $ret[1]);
        $this->assertEquals('text/html', $ret[0]->getValue());
        $this->assertEquals('charset', $ret[1]->getName());
        $this->assertEquals('utf8', $ret[1]->getValue());
    }

    public function testEscapedSeparators()
    {
        $ret = $this->parameterConsumer->__invoke('test\;with\;special\=chars; and\=more=blah');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('test;with;special=chars', $ret[0]->getValue());
        $this->assertEquals('and=more', $ret[1]->getName());
        $this->assertEquals('blah', $ret[1]->getValue());
    }

    public function testWithSubConsumers()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; weiner="all-beef";toppings=sriracha (boo-yah!)');
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('weiner', $ret[1]->getName());
        $this->assertEquals('all-beef', $ret[1]->getValue());
        $this->assertEquals('toppings', $ret[2]->getName());
        $this->assertEquals('sriracha', $ret[2]->getValue());
    }

    public function testQuotedWithRfc2047Value()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments="=?US-ASCII?Q?mustard?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard', $ret[1]->getValue());
    }

    public function testUnquotedWithRfc2047Value()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments==?US-ASCII?Q?mustard?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard', $ret[1]->getValue());
    }

    public function testSimpleSplitHeaderWithDoubleQuotedParts()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="mustar";'
            . 'condiments*1="d, ketchup"; condiments*2=" and mayo"');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard, ketchup and mayo', $ret[1]->getValue());
        $this->assertNull($ret[1]->getLanguage());
    }

    public function testSplitHeaderInFunnyOrder()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*2=" and mayo";'
            . 'condiments*1="d, ketchup"; condiments*0="mustar"');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard, ketchup and mayo', $ret[1]->getValue());
        $this->assertNull($ret[1]->getLanguage());
    }

    public function testSplitHeaderWithEmptyEncodingAndLanguage()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*=\'\''
            . 'mustard,%20ketchup%20and%20mayo');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard, ketchup and mayo', $ret[1]->getValue());
        $this->assertNull($ret[1]->getLanguage());
    }

    public function testSplitHeaderWithEncodingAndLanguage()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*=us-ascii\'en-US\''
            . 'mustard,%20ketchup%20and%20mayo');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard, ketchup and mayo', $ret[1]->getValue());
        $this->assertEquals('en-US', $ret[1]->getLanguage());
    }

    public function testSplitHeaderWithEncodingLanguageAndQuotedPart()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0*=us-ascii\'en-US\''
            . 'mustard,%20ketchup; condiments*1*=%20and; condiments*2=" mayo"');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard, ketchup and mayo', $ret[1]->getValue());
        $this->assertEquals('en-US', $ret[1]->getLanguage());
    }

    public function testSplitHeaderWithEncodingLanguageAndQuotedPartAndWrongNumbering()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*1*=us-ascii\'en-US\''
            . 'mustard,%20ketchup; condiments*2*=%20and; condiments*3=" mayo"');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard, ketchup and mayo', $ret[1]->getValue());
        // $this->assertEquals('en-US', $ret[1]->getLanguage());
    }

    public function testSplitHeaderWithMultiByteEncodedPart()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*=utf-8\'\''
            . 'mustardized%E2%80%93ketchup');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustardized–ketchup', $ret[1]->getValue());
        $this->assertNull($ret[1]->getLanguage());
    }

    public function testSplitHeaderWithMultiByteEncodedPartAndLanguage()
    {
        $str = 'هلا هلا شخبار بعد؟ شلون تبرمج؟';
        $encoded = rawurlencode($str);
        $halfPos = floor((strlen($encoded) / 3) / 2) * 3;
        $part1 = substr($encoded, 0, $halfPos);
        $part2 = substr($encoded, $halfPos);

        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0*=utf-8\'abv-BH\''. $part1
            . '; condiments*1*=' . $part2);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals($str, $ret[1]->getValue());
        $this->assertEquals('abv-BH', $ret[1]->getLanguage());
    }

    public function testSplitHeaderWithRfc2047()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*=\'\''
            . '=?US-ASCII?Q?TS_Eliot?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS Eliot', $ret[1]->getValue());
    }

    public function testSplitHeaderWithSplitRfc2047()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="'
            . '=?US-ASCII?Q?TS_Eli"; condiments*1="ot?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS Eliot', $ret[1]->getValue());
    }

    public function testSplitHeaderWithMultipleSplitRfc2047()
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="'
            . '=?US-ASCII?Q?TS_E?=   =?US-ASCII?Q?li"; condiments*1="ot?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS Eliot', $ret[1]->getValue());
    }
}
