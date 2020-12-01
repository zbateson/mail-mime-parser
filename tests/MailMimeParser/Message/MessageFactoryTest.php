<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7;

/**
 * MessageFactoryTest
 *
 * @group MessageFactory
 * @group Message
 * @covers ZBateson\MailMimeParser\Message\MessageFactory
 * @author Zaahid Bateson
 */
class MessageFactoryTest extends TestCase
{
    protected $messageFactory;

    protected function setUp(): void
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $mockpsfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockpsfmfactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $mockFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $mockHelperService = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MessageHelperService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockpsfmfactory->method('newInstance')
            ->willReturn($mockpsfm);

        $this->messageFactory = new MessageFactory(
            $mocksdf,
            $mockpsfmfactory,
            $mockFilterFactory,
            $mockHelperService
        );
    }

    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $part = $this->messageFactory->newInstance(
            $partBuilder,
            Psr7\stream_for('test')
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message',
            $part
        );
    }
}
