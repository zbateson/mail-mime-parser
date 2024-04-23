<?php

namespace ZBateson\MailMimeParser\Stream;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Message\IMessagePart;

/**
 * MessagePartStreamReadExceptionTest
 *
 * @group MessagePartStreamReadException
 * @group Stream
 * @covers ZBateson\MailMimeParser\Stream\MessagePartStreamReadException
 * @author Zaahid Bateson
 */
class MessagePartStreamReadExceptionTest extends TestCase
{
    public function testInstance() : void
    {
        $part = $this->getMockForAbstractClass(IMessagePart::class);
        $exc = new MessagePartStreamReadException($part);
        $this->assertSame($part, $exc->getPart());
    }
}
