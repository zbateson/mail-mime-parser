<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of ParameterConsumerServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(ParameterConsumerService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[Group('Consumers')]
#[Group('ParameterConsumerService')]
class ParameterConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $parameterConsumer;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->onlyMethods([])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->onlyMethods([])
            ->getMock();
        $qsmlpcs = $this->getMockBuilder(QuotedStringMimeLiteralPartConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $pvcs = $this->getMockBuilder(ParameterValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $ccs, $qsmlpcs])
            ->onlyMethods([])
            ->getMock();
        $pnvcs = $this->getMockBuilder(ParameterNameValueConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $pvcs, $ccs, $qscs])
            ->onlyMethods([])
            ->getMock();
        $this->parameterConsumer = new ParameterConsumerService($this->logger, $pf, $pnvcs, $ccs, $qscs);
    }

    public function testConsumeTokens() : void
    {
        $ret = $this->parameterConsumer->__invoke('text/html; charset=utf8');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ContainerPart::class, $ret[0]);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\ParameterPart::class, $ret[1]);
        $this->assertEquals('text/html', $ret[0]->getValue());
        $this->assertEquals('charset', $ret[1]->getName());
        $this->assertEquals('utf8', $ret[1]->getValue());
    }

    public function testEscapedSeparators() : void
    {
        $ret = $this->parameterConsumer->__invoke('test\;with\;special\=chars; and\=more=blah');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('test;with;special=chars', $ret[0]->getValue());
        $this->assertEquals('and=more', $ret[1]->getName());
        $this->assertEquals('blah', $ret[1]->getValue());
    }

    public function testWithSubConsumers() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; weiner="all-beef";toppings=sriracha (boo-yah!)');
        $this->assertNotEmpty($ret);
        $this->assertCount(3, $ret);
        $comments = $ret[2]->getComments();
        $this->assertNotEmpty($comments);
        $this->assertCount(1, $comments);

        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('weiner', $ret[1]->getName());
        $this->assertEquals('all-beef', $ret[1]->getValue());
        $this->assertEquals('boo-yah!', $comments[0]->getComment());
        $this->assertEquals('toppings', $ret[2]->getName());
        $this->assertEquals('sriracha', $ret[2]->getValue());
    }

    public function testQuotedWithRfc2047Value() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments="=?US-ASCII?Q?mustard?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard', $ret[1]->getValue());
    }

    public function testUnquotedWithRfc2047Value() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments==?US-ASCII?Q?mustard?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals('mustard', $ret[1]->getValue());
    }

    public function testSimpleSplitHeaderWithDoubleQuotedParts() : void
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

    public function testSplitHeaderInFunnyOrder() : void
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

    public function testSplitHeaderWithEmptyEncodingAndLanguage() : void
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

    public function testSplitHeaderWithEncodingAndLanguage() : void
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

    public function testSplitHeaderWithEncodingLanguageAndQuotedPart() : void
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

    public function testSplitHeaderWithEncodingLanguageAndQuotedPartAndWrongNumbering() : void
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

    public function testSplitHeaderWithMultiByteEncodedPart() : void
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

    public function testSplitHeaderWithMultiByteEncodedPartAndLanguage() : void
    {
        $str = 'هلا هلا شخبار بعد؟ شلون تبرمج؟';
        $encoded = \rawurlencode($str);
        $halfPos = (int) \floor((\strlen($encoded) / 3) / 2) * 3;
        $part1 = \substr($encoded, 0, $halfPos);
        $part2 = \substr($encoded, $halfPos);

        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0*=utf-8\'abv-BH\'' . $part1
            . '; condiments*1*=' . $part2);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('hotdogs', $ret[0]->getValue());
        $this->assertEquals('condiments', $ret[1]->getName());
        $this->assertEquals($str, $ret[1]->getValue());
        $this->assertEquals('abv-BH', $ret[1]->getLanguage());
    }

    public function testSplitHeaderWithRfc2047() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*=\'\''
            . '=?US-ASCII?Q?TS_Eliot?=');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS Eliot', $ret[1]->getValue());
    }

    public function testSplitHeaderWithQuotedValueContainingDoubleApos() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="'
            . 'That\'s \'gotta\' hurt"');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('That\'s \'gotta\' hurt', $ret[1]->getValue());
    }

    public function testSplitHeaderWithSplitRfc2047() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="'
            . '=?US-ASCII?Q?TS_Eli"; condiments*1="ot?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS Eliot', $ret[1]->getValue());
    }

    public function testSplitHeaderWithMultipleSplitRfc2047() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="'
            . '=?US-ASCII?Q?TS_E?=   =?US-ASCII?Q?li"; condiments*1="ot?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS E liot', $ret[1]->getValue());
    }

    public function testSplitHeaderWithContinuedRfc2047AcrossParts() : void
    {
        $ret = $this->parameterConsumer->__invoke('hotdogs; condiments*0="'
            . '=?US-ASCII?Q?TS_E"; condiments*1="liot?="');
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('TS Eliot', $ret[1]->getValue());
    }
}
