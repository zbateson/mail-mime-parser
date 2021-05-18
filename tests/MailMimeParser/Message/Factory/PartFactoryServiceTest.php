<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;
use ZBateson\MailMimeParser\Container;

/**
 * PartFactoryServiceTest
 *
 * @group PartFactoryService
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Parser\Part\PartFactoryService
 * @author Zaahid Bateson
 */
class PartFactoryServiceTest extends TestCase
{
    public function testInstance()
    {
        $di = new Container();
        $partFactoryService = $di->getPartFactoryService();

        $messageFactory = $partFactoryService->getMessageFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Parser\Part\MessageFactory', $messageFactory);

        $mimePartFactory = $partFactoryService->getMimePartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Parser\Part\MimePartFactory', $mimePartFactory);

        $nonMimePartFactory = $partFactoryService->getNonMimePartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Parser\Part\NonMimePartFactory', $nonMimePartFactory);

        $uuEncodedPartFactory = $partFactoryService->getUUEncodedPartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Parser\Part\UUEncodedPartFactory', $uuEncodedPartFactory);
    }
}
