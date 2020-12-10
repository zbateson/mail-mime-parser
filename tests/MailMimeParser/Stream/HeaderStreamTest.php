<?php
namespace ZBateson\MailMimeParser\Stream;

use ArrayIterator;
use LegacyPHPUnit\TestCase;

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
    private function newMockMimePart()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\MimePart')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function newMockUUEncodedPart()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\UUEncodedPart')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testReadWithMimePart()
    {
        $mimePart = $this->newMockMimePart();
        $mimePart->expects($this->once())
            ->method('getRawHeaderIterator')
            ->willReturn(new ArrayIterator([
                [ 'First-Header', 'Missed by a long-shot' ],
                [ 'Second-Header', 'Gooaaaaaaaaal' ]
            ]));

        $stream = new HeaderStream($mimePart);
        $this->assertEquals(
            "First-Header: Missed by a long-shot\r\nSecond-Header: Gooaaaaaaaaal\r\n\r\n",
            $stream->getContents()
        );
    }

    public function testReadWithUUEncodedPart()
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
