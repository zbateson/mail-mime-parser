<?php
namespace ZBateson\MailMimeParser\Header\Consumer\Received;

use PHPUnit\Framework\TestCase;

/**
 * Description of GenericReceivedConsumerTest
 *
 * @group Consumers
 * @group GenericReceivedConsumer
 * @covers ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumer
 * @author Zaahid Bateson
 */
class GenericReceivedConsumerTest extends TestCase
{
    private $genericConsumer;

    protected function setUp()
    {
        $charsetConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
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
        $this->genericConsumer = new GenericReceivedConsumer($cs, $pf, 'test');
    }

    public function testConsumeTokens()
    {
        $value = "Je \t suis\nici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je suis ici', $ret[0]);
    }

    public function testEndsAtViaWithIdAndFor()
    {
        $tests = [
            'sweet via sugar',
            'sweet with honey',
            'sweet id 1',
            'sweet for you'
        ];
        foreach ($tests as $t) {
            $ret = $this->genericConsumer->__invoke($t);
            $this->assertNotEmpty($ret);
            $this->assertCount(1, $ret);
            $this->assertEquals('sweet', $ret[0]);
        }
    }

    public function testWithSingleComments()
    {
        $str = 'sweet (via sugar) bee';
        $ret = $this->genericConsumer->__invoke($str);
        $this->assertNotEmpty($ret);
        $this->assertCount(2, $ret);
        $this->assertEquals('sweet bee', $ret[0]);
        $this->assertEquals('via sugar', $ret[1]->getComment());
    }

    public function testWithMultipleComments()
    {
        $str = 'sweet (as can) (surely) bee (innit)';
        $ret = $this->genericConsumer->__invoke($str);
        $this->assertNotEmpty($ret);
        $this->assertCount(4, $ret);
        $this->assertEquals('sweet bee', $ret[0]);
        $this->assertEquals('as can', $ret[1]->getComment());
        $this->assertEquals('surely', $ret[2]->getComment());
        $this->assertEquals('innit', $ret[3]->getComment());
    }

    public function testWithSeparatorInWords()
    {
        $str = 'bullets within abe and stuff';
        $ret = $this->genericConsumer->__invoke($str);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('bullets within abe and stuff', $ret[0]);
    }
}
