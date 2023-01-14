<?php

namespace ZBateson\MailMimeParser\Stream;

use ArrayIterator;
use PHPUnit\Framework\TestCase;

/**
 * HeaderStreamTest
 *
 * @group HeaderStream
 * @group Stream
 * @covers ZBateson\MailMimeParser\Stream\HeaderStream
 * @author Zaahid Bateson
 */
class HeaderStreamTest extends TestCase
{
    private function newMockMimePart() : \ZBateson\MailMimeParser\Message\MimePart
    {
        return $this->getMockBuilder(\ZBateson\MailMimeParser\Message\MimePart::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockUUEncodedPart() : \ZBateson\MailMimeParser\Message\UUEncodedPart
    {
        return $this->getMockBuilder(\ZBateson\MailMimeParser\Message\UUEncodedPart::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testReadWithMimePart() : void
    {
        $mimePart = $this->newMockMimePart();
        $mimePart->expects($this->once())
            ->method('getRawHeaderIterator')
            ->willReturn(new ArrayIterator([
                ['First-Header', 'Missed by a long-shot'],
                ['Second-Header', 'Gooaaaaaaaaal']
            ]));

        $stream = new HeaderStream($mimePart);
        $this->assertEquals(
            "First-Header: Missed by a long-shot\r\nSecond-Header: Gooaaaaaaaaal\r\n\r\n",
            $stream->getContents()
        );
    }

    public function testReadWithUUEncodedPart() : void
    {
        $uuPart = $this->newMockUUEncodedPart();
        $stream = new HeaderStream($uuPart);
        $this->assertEquals("\r\n", $stream->getContents());

        $uuPart->expects($this->once())
            ->method('getContentType')
            ->willReturn('text/beans');
        $uuPart->expects($this->once())
            ->method('getContentDisposition')
            ->willReturn('mild-mannered');
        $uuPart->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('illegible');

        $mimePart = $this->newMockMimePart();
        $mimePart->expects($this->once())
            ->method('isMime')
            ->willReturn(true);
        $uuPart->expects($this->atLeastOnce())
            ->method('getParent')
            ->willReturn($mimePart);

        $stream = new HeaderStream($uuPart);
        $this->assertEquals(
            "Content-Type: text/beans\r\n"
            . "Content-Disposition: mild-mannered\r\n"
            . "Content-Transfer-Encoding: illegible\r\n\r\n",
            $stream->getContents()
        );
    }
}
