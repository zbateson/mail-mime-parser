<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Header\IHeader;

/**
 * Description of HeaderContainerTest
 *
 * @group Message
 * @group PartHeaderContainer
 * @covers ZBateson\MailMimeParser\Message\PartHeaderContainer
 * @author Zaahid Bateson
 */
class PartHeaderContainerTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $mockHeaderFactory;

    protected function setUp() : void
    {
        $this->mockHeaderFactory = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\HeaderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['newInstance', 'newInstanceOf'])
            ->getMock();
    }

    public function testAddExistsGet() : void
    {
        $ob = new PartHeaderContainer($this->mockHeaderFactory);
        $ob->add('first', 'value');
        $ob->add('second', 'value');

        $this->assertTrue($ob->exists('first'));
        $this->assertTrue($ob->exists('second'));
        $this->assertFalse($ob->exists('third'));
        $this->assertFalse($ob->exists('first', 1));

        $mockFirstHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondHeader = $this->getMockBuilder(IHeader::class)->getMock();

        $this->mockHeaderFactory
            ->expects($this->exactly(2))
            ->method('newInstance')
            ->withConsecutive(
                ['first', 'value'],
                ['second', 'value']
            )
            ->willReturnOnConsecutiveCalls($mockFirstHeader, $mockSecondHeader);

        $this->assertSame($mockFirstHeader, $ob->get('first'));
        $this->assertSame($mockSecondHeader, $ob->get('second'));
        $this->assertSame($mockFirstHeader, $ob->get('first', 0));
        $this->assertSame($mockFirstHeader, $ob->get('first'));
        $this->assertSame($mockSecondHeader, $ob->get('second', 0));
        $this->assertSame($mockSecondHeader, $ob->get('second'));

        $this->assertNull($ob->get('other'));
        $this->assertNull($ob->get('second', 1));

        $headers = [['first', 'value'], ['second', 'value']];
        $this->assertEquals($headers, $ob->getHeaders());
    }

    public function testAddExistsGetSameName() : void
    {
        $ob = new PartHeaderContainer($this->mockHeaderFactory);
        $ob->add('repeated', 'first');
        $ob->add('repeated', 'second');
        $ob->add('repeated', 'third');

        $this->assertTrue($ob->exists('repeated'));
        $this->assertTrue($ob->exists('repeated', 0));
        $this->assertTrue($ob->exists('repeated', 1));
        $this->assertTrue($ob->exists('repeated', 2));
        $this->assertFalse($ob->exists('repeated', 3));
        $this->assertFalse($ob->exists('something-else'));

        $mockFirstHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockThirdHeader = $this->getMockBuilder(IHeader::class)->getMock();

        $this->mockHeaderFactory
            ->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                ['repeated', 'first'],
                ['repeated', 'second'],
                ['repeated', 'third']
            )
            ->willReturnOnConsecutiveCalls($mockFirstHeader, $mockSecondHeader, $mockThirdHeader);

        $this->assertSame($mockFirstHeader, $ob->get('repeated'));
        $this->assertSame($mockFirstHeader, $ob->get('repeated', 0));
        $this->assertSame($mockSecondHeader, $ob->get('repeated', 1));
        $this->assertSame($mockThirdHeader, $ob->get('repeated', 2));

        $instanceHeaders = [
            $mockFirstHeader, $mockSecondHeader, $mockThirdHeader
        ];
        $this->assertEquals($instanceHeaders, $ob->getAll('repeated'));

        $this->assertNull($ob->get('other'));
        $this->assertNull($ob->get('repeated', 3));

        $headers = [
            ['repeated', 'first'],
            ['repeated', 'second'],
            ['repeated', 'third']
        ];
        $this->assertEquals($headers, $ob->getHeaders());
    }

    public function testAddSetExistsGet() : void
    {
        $ob = new PartHeaderContainer($this->mockHeaderFactory);
        $ob->set('first', 'value');
        $ob->set('second', 'value');
        $ob->set('third', 'value');

        $ob->add('first', 'second-first');
        $ob->add('second', 'second-second');

        $ob->set('first', 'updated-value');
        $ob->set('second', 'second-updated-value', 1);

        $this->assertTrue($ob->exists('first'));
        $this->assertTrue($ob->exists('first', 1));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('second', 1));
        $this->assertTrue($ob->exists('third'));

        $mockFirstUpdatedHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondUpdatedHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondFirstHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockThirdHeader = $this->getMockBuilder(IHeader::class)->getMock();

        $this->mockHeaderFactory
            ->expects($this->exactly(5))
            ->method('newInstance')
            ->withConsecutive(
                ['first', 'updated-value'],
                ['second', 'second-updated-value'],
                ['first', 'second-first'],
                ['second', 'value'],
                ['third', 'value']
            )
            ->willReturnOnConsecutiveCalls(
                $mockFirstUpdatedHeader,
                $mockSecondUpdatedHeader,
                $mockSecondFirstHeader,
                $mockSecondHeader,
                $mockThirdHeader
            );

        $this->assertSame($mockFirstUpdatedHeader, $ob->get('first'));
        $this->assertSame($mockSecondUpdatedHeader, $ob->get('second', 1));
        $this->assertSame($mockSecondFirstHeader, $ob->get('first', 1));
        $this->assertSame($mockSecondHeader, $ob->get('second'));
        $this->assertSame($mockThirdHeader, $ob->get('third'));

        $instanceHeaders = [
            $mockFirstUpdatedHeader, $mockSecondFirstHeader
        ];
        $this->assertEquals($instanceHeaders, $ob->getAll('first'));

        $headers = [
            ['first', 'updated-value'],
            ['second', 'value'],
            ['third', 'value'],
            ['first', 'second-first'],
            ['second', 'second-updated-value']
        ];
        $this->assertEquals($headers, $ob->getHeaders());
    }

    public function testAddRemoveGetGetAs() : void
    {
        $ob = new PartHeaderContainer($this->mockHeaderFactory);
        $ob->add('first', 'value');
        $ob->add('second', 'value');
        $ob->add('third', 'value');
        $ob->add('fourth', 'value');

        $this->assertTrue($ob->exists('first'));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('third'));
        $this->assertTrue($ob->exists('fourth'));

        $ob->remove('first');

        $this->assertFalse($ob->exists('first'));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('third'));
        $this->assertTrue($ob->exists('fourth'));

        $mockFirstHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockThirdHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondUpdatedHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $this->mockHeaderFactory
            ->expects($this->exactly(4))
            ->method('newInstance')
            ->withConsecutive(
                ['second', 'value'],
                ['third', 'value'],
                ['fourth', 'value'],
                ['second', 'updated']
            )
            ->willReturnOnConsecutiveCalls($mockFirstHeader, $mockSecondHeader, $mockThirdHeader, $mockSecondUpdatedHeader);

        $custRet = $this->getMockForAbstractClass(\ZBateson\MailMimeParser\Header\IHeader::class);
        $this->mockHeaderFactory->expects($this->once())
            ->method('newInstanceOf')
            ->with('fourth', 'value', 'IHeaderClass')
            ->willReturn($custRet);

        $this->assertNull($ob->get('first'));
        $this->assertSame($mockFirstHeader, $ob->get('second'));
        $this->assertSame($mockSecondHeader, $ob->get('third'));
        $this->assertSame($mockThirdHeader, $ob->get('fourth'));
        $headers = [
            ['second', 'value'],
            ['third', 'value'],
            ['fourth', 'value'],
        ];
        $this->assertEquals($headers, $ob->getHeaders());

        $ob->remove('second');
        $headers = [
            ['third', 'value'],
            ['fourth', 'value']
        ];
        $this->assertNull($ob->get('second'));
        $this->assertSame($mockSecondHeader, $ob->get('third'));
        $this->assertEquals($headers, $ob->getHeaders());

        $ob->set('second', 'updated');
        $headers = [
            ['third', 'value'],
            ['fourth', 'value'],
            ['second', 'updated']
        ];
        $this->assertEquals($headers, $ob->getHeaders());
        $this->assertSame($mockSecondUpdatedHeader, $ob->get('second'));

        $h = $ob->getAs('fourth', 'IHeaderClass');
        $this->assertEquals($custRet, $h);
    }

    public function testAddRemoveAllGet() : void
    {
        $ob = new PartHeaderContainer($this->mockHeaderFactory);
        $ob->add('first', 'value');
        $ob->add('first', 'second-first');
        $ob->add('second', 'value');
        $ob->add('second', 'second-second');
        $ob->add('second', 'third-second');
        $ob->add('third', 'value');

        $this->assertTrue($ob->exists('FIRST'));
        $this->assertTrue($ob->exists('first', 1));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('SECOND', 1));
        $this->assertTrue($ob->exists('second', 2));
        $this->assertTrue($ob->exists('third'));

        $ob->remove('FIRST');

        $this->assertTrue($ob->exists('FiRST'));
        $this->assertFalse($ob->exists('fIRst', 1));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('second', 1));
        $this->assertTrue($ob->exists('second', 2));
        $this->assertTrue($ob->exists('third'));

        $mockSecondFirstHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $mockSecondThirdSecondHeader = $this->getMockBuilder(IHeader::class)->getMock();
        $this->mockHeaderFactory
            ->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                ['first', 'second-first'],
                ['second', 'value'],
                ['second', 'third-second']
            )
            ->willReturnOnConsecutiveCalls($mockSecondFirstHeader, $mockSecondHeader, $mockSecondThirdSecondHeader);

        $this->assertNull($ob->get('first', 1));
        $this->assertSame($mockSecondFirstHeader, $ob->get('first'));

        $ob->remove('second', 1);
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('second', 1));
        $this->assertFalse($ob->exists('second', 2));

        $this->assertSame($mockSecondHeader, $ob->get('second'));
        $this->assertSame($mockSecondThirdSecondHeader, $ob->get('second', 1));
        $this->assertNull($ob->get('second', 2));

        $ob->removeAll('second');
        $this->assertFalse($ob->exists('second'));

        $headers = [
            ['first', 'second-first'],
            ['third', 'value'],
        ];
        $this->assertEquals($headers, $ob->getHeaders());

        $ob->set('second', 'new-value', 3);
        $headers[] = ['second', 'new-value'];
        $this->assertEquals($headers, $ob->getHeaders());
    }
}
