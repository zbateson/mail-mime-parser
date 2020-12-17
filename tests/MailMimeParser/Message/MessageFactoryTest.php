<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * MessageFactoryTest
 *
 * @group MessageFactory
 * @group Message
 * @covers ZBateson\MailMimeParser\Parser\Part\MessageFactory
 * @author Zaahid Bateson
 */
class MessageFactoryTest extends TestCase
{
    protected $messageFactory;

    protected function legacySetUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $mockpsfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockpsfmfactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\PartStreamFilterManagerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $mockFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\MessageFilterFactory')
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
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
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
