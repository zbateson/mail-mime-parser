<?php

namespace ZBateson\MailMimeParser\Message;

use Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Stream\MessagePartStreamDecorator;

/**
 * MessagePartTest
 *
 * @group MessagePartClass
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\MessagePart
 * @author Zaahid Bateson
 */
class MessagePartTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $partStreamContainer;

    protected function setUp() : void
    {
        $this->partStreamContainer = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\PartStreamContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMessagePart($handle = 'habibi', $contentHandle = null, $parent = null) : \ZBateson\MailMimeParser\Message\MessagePart
    {
        $streamPartMock = $this->getMockForAbstractClass(
            MessagePart::class,
            [new NullLogger(), $this->partStreamContainer, $parent]
        );
        if ($contentHandle !== null) {
            $contentHandle = new MessagePartStreamDecorator($streamPartMock, Psr7\Utils::streamFor($contentHandle));
            $this->partStreamContainer
                ->method('getContentStream')
                ->willReturnCallback(function() use ($contentHandle) {
                    try {
                        $contentHandle->rewind();
                    } catch (Exception $e) {

                    }
                    return $contentHandle;
                });
        }
        if ($handle !== null) {
            $handle = new MessagePartStreamDecorator($streamPartMock, Psr7\Utils::streamFor($handle));
            $this->partStreamContainer
                ->method('getStream')
                ->willReturnCallback(function() use ($handle) {
                    try {
                        $handle->rewind();
                    } catch (Exception $e) {

                    }
                    return $handle;
                });
        }
        return $this->getMockForAbstractClass(
            MessagePart::class,
            [new NullLogger(), $this->partStreamContainer, $parent]
        );
    }

    public function testNotify() : void
    {
        $messagePart = $this->getMessagePart();
        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $messagePart->attach($observer);
        $messagePart->notify();
        $messagePart->detach($observer);
        $messagePart->notify();
    }

    public function testParentAndParentNotify() : void
    {
        $parent = $this->getMockBuilder(\ZBateson\MailMimeParser\Message\MimePart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $messagePart = $this->getMessagePart('blah', 'blooh', $parent);

        $this->assertSame($parent, $messagePart->getParent());
        $parent->expects($this->once())->method('notify');
        $messagePart->notify();
    }

    public function testStreams() : void
    {
        $messagePart = $this->getMessagePart();
        $this->assertNotNull($messagePart);

        $this->partStreamContainer->expects($this->atLeastOnce())->method('hasContent')->willReturn(false);
        $this->assertFalse($messagePart->hasContent());

        $this->assertNull($messagePart->getContentStream());
        $this->assertNull($messagePart->getContent());
        $this->assertNull($messagePart->getParent());
        $this->assertEquals('habibi', \stream_get_contents($messagePart->getResourceHandle()));
        $this->assertEquals('habibi', $messagePart->getStream()->getContents());
    }

    public function testGetFilenameReturnsNull() : void
    {
        $messagePart = $this->getMessagePart();
        $this->assertNull($messagePart->getFilename());
    }

    public function testGetContent() : void
    {
        $messagePart = $this->getMessagePart('habibi', 'sopa di agua con rocas');
        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $this->assertEquals('sopa di agua con rocas', $messagePart->getContent());
    }

    public function testContentStreamAndCharsetOverride() : void
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Que tonto');
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');
        $messagePart->method('getCharset')
            ->willReturn('wigidiwamwamwazzle');

        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $stream = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));
        $this->partStreamContainer->expects($this->exactly(2))
            ->method('getContentStream')
            ->withConsecutive(
                [$messagePart, 'wubalubadub-duuuuub', 'wigidiwamwamwazzle', 'oooohweee!'],
                [$messagePart, 'wubalubadub-duuuuub', 'override', 'oooohweee!']
            )
            ->willReturn($stream);

        $this->assertEquals('Que tonto', $messagePart->getContentStream('oooohweee!')->getContents());
        $messagePart->setCharsetOverride('override');
        $this->assertEquals('Que tonto', $messagePart->getContentStream('oooohweee!')->getContents());
    }

    public function testBinaryContentStream() : void
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Setup');
        $f = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('First'));
        $s = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Second'));

        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');

        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $this->partStreamContainer
            ->expects($this->never())
            ->method('getContentStream');
        $this->partStreamContainer
            ->expects($this->exactly(2))
            ->method('getBinaryContentStream')
            ->willReturnOnConsecutiveCalls($f, $s);

        $this->assertEquals('First', $messagePart->getBinaryContentStream()->getContents());
        $this->assertEquals('Second', \stream_get_contents($messagePart->getBinaryContentResourceHandle()));
    }

    public function testSaveContent() : void
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Setup');
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');
        $f = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));
        $s = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));

        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $this->partStreamContainer
            ->expects($this->never())
            ->method('getContentStream');
        $this->partStreamContainer
            ->expects($this->once())
            ->method('getBinaryContentStream')
            ->willReturnOnConsecutiveCalls($f, $s);

        $file = tempnam(sys_get_temp_dir(), 'mmp_test_save_content');
        $messagePart->saveContent($file);
        $this->assertEquals('Que tonto', \file_get_contents($file));
        unlink($file);
    }

    public function testSaveContentToStream() : void
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Setup');
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');
        $f = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));
        $s = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));

        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $this->partStreamContainer
            ->expects($this->never())
            ->method('getContentStream');
        $this->partStreamContainer
            ->expects($this->once())
            ->method('getBinaryContentStream')
            ->willReturnOnConsecutiveCalls($f, $s);

        $stream = Psr7\Utils::streamFor();
        $messagePart->saveContent($stream);
        $stream->rewind();

        $this->assertEquals('Que tonto', $stream->getContents());
    }

    public function testSaveContentToResource() : void
    {
        $messagePart = $this->getMessagePart('Que tonta', 'Setup');
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('wubalubadub-duuuuub');
        $f = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));
        $s = new MessagePartStreamDecorator($messagePart, Psr7\Utils::streamFor('Que tonto'));

        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $this->partStreamContainer
            ->expects($this->never())
            ->method('getContentStream');
        $this->partStreamContainer
            ->expects($this->once())
            ->method('getBinaryContentStream')
            ->willReturnOnConsecutiveCalls($f, $s);

        $res = StreamWrapper::getResource(Psr7\Utils::streamFor());
        $messagePart->saveContent($res);
        \rewind($res);

        $this->assertEquals('Que tonto', \stream_get_contents($res));
        \fclose($res);
    }

    public function testDetachContentStream() : void
    {
        $stream = Psr7\Utils::streamFor('Que tonta');
        $contentStream = Psr7\Utils::streamFor('Que tonto');
        $messagePart = $this->getMessagePart($stream, $contentStream);

        $this->partStreamContainer
            ->expects($this->once())
            ->method('setContentStream')
            ->with(null);

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $messagePart->attach($observer);

        $messagePart->detachContentStream();
    }

    public function testSetContentAndAttachContentStream() : void
    {
        $ms = Psr7\Utils::streamFor('message');
        $org = Psr7\Utils::streamFor('content');
        $messagePart = $this->getMessagePart($ms, $org);
        $messagePart->method('getContentTransferEncoding')
            ->willReturn('quoted-printable');
        $messagePart->method('getCharset')
            ->willReturn('utf-64');

        $new = Psr7\Utils::streamFor('updated');
        $this->partStreamContainer->method('hasContent')->willReturn(true);
        $this->partStreamContainer
            ->method('getContentStream')
            ->withConsecutive(
                [$messagePart, '', 'charset', 'a-charset']
            );

        $this->assertEquals('message', $messagePart->getStream()->getContents());

        $this->partStreamContainer
            ->method('setContentStream')
            ->with($new);

        $observer = $this->getMockForAbstractClass('SplObserver');
        $observer->expects($this->once())
            ->method('update');
        $messagePart->attach($observer);

        $messagePart->setContent($new, 'charset');

        // actually returns $org because of method definition in getMessagePart
        $messagePart->getContentStream('a-charset');
    }

    public function testSaveAndToString() : void
    {
        $messagePart = $this->getMessagePart(
            'Demigorgon',
            Psr7\Utils::streamFor('other demons')
        );

        $handle = \fopen('php://temp', 'r+');
        $messagePart->save($handle);
        \rewind($handle);
        $str = \stream_get_contents($handle);
        \fclose($handle);

        $this->assertEquals('Demigorgon', $str);
        $this->assertEquals('Demigorgon', $messagePart->__toString());
    }

    public function testSaveToFile() : void
    {
        $messagePart = $this->getMessagePart(
            'Demigorgon',
            Psr7\Utils::streamFor('other demons')
        );

        $file = tempnam(sys_get_temp_dir(), 'mmp_test_save_to_file');
        $messagePart->save($file);
        $this->assertEquals('Demigorgon', \file_get_contents($file));
        unlink($file);
    }
}
