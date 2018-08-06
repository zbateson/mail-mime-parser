<?php
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\Container;

/**
 * PartFactoryServiceTest
 *
 * @group PartFactoryService
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\PartFactoryService
 * @author Zaahid Bateson
 */
class PartFactoryServiceTest extends TestCase
{
    public function testInstance()
    {
        $di = new Container();
        $partFactoryService = $di->getPartFactoryService();

        $messageFactory = $partFactoryService->getMessageFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\MessageFactory', $messageFactory);

        $mimePartFactory = $partFactoryService->getMimePartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory', $mimePartFactory);

        $nonMimePartFactory = $partFactoryService->getNonMimePartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\Factory\NonMimePartFactory', $nonMimePartFactory);

        $uuEncodedPartFactory = $partFactoryService->getUUEncodedPartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\Factory\UUEncodedPartFactory', $uuEncodedPartFactory);
    }
}
