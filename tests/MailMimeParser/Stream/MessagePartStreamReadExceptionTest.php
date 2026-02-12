<?php

namespace ZBateson\MailMimeParser\Stream;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Message\IMessagePart;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * MessagePartStreamReadExceptionTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MessagePartStreamReadException::class)]
#[Group('MessagePartStreamReadException')]
#[Group('Stream')]
class MessagePartStreamReadExceptionTest extends TestCase
{
    public function testInstance() : void
    {
        $part = $this->getMockForAbstractClass(IMessagePart::class);
        $exc = new MessagePartStreamReadException($part);
        $this->assertSame($part, $exc->getPart());
    }
}
