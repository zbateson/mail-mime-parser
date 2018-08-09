<?php
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7;

/**
 * MimePartFactoryTest
 *
 * @group MimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory
 * @author Zaahid Bateson
 */
class MimePartFactoryTest extends TestCase
{
    protected $mimePartFactory;
    protected $partFilterFactory;

    protected function setUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $mocksdf->expects($this->any())
            ->method('getLimitedPartStream')
            ->willReturn(Psr7\stream_for('test'));
        $psfmFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\PartStreamFilterManagerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $psfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $psfmFactory
            ->method('newInstance')
            ->willReturn($psfm);

        $mockFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mimePartFactory = new MimePartFactory($mocksdf, $psfmFactory, $mockFilterFactory);
    }

    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $part = $this->mimePartFactory->newInstance(
            $partBuilder,
            Psr7\stream_for('test')
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\MimePart',
            $part
        );
    }
}
