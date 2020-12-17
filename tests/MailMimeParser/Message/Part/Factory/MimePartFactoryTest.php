<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;

/**
 * MimePartFactoryTest
 *
 * @group MimePartFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Parser\Part\MimePartFactory
 * @covers ZBateson\MailMimeParser\Parser\Part\MessagePartFactory
 * @author Zaahid Bateson
 */
class MimePartFactoryTest extends TestCase
{
    protected $mimePartFactory;
    protected $partFilterFactory;

    protected function legacySetUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $mocksdf->expects($this->any())
            ->method('getLimitedPartStream')
            ->willReturn(Psr7\stream_for('test'));
        $psfmFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\PartStreamFilterManagerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $psfm = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartStreamFilterManager')
            ->disableOriginalConstructor()
            ->getMock();
        $psfmFactory
            ->method('newInstance')
            ->willReturn($psfm);

        $mockFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\MessageFilterFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mimePartFactory = new MimePartFactory($mocksdf, $psfmFactory, $mockFilterFactory);
    }

    public function testNewInstance()
    {
        $partBuilder = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $part = $this->mimePartFactory->newInstance(
            $partBuilder,
            Psr7\stream_for('test')
        );
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\MimePart',
            $part
        );
    }
}
