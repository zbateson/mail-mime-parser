<?php
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\SimpleDi;

/**
 * PartFactoryServiceTest
 * 
 * @group PartFactoryService
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\PartFactoryService
 * @author Zaahid Bateson
 */
class PartFactoryServiceTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $di = SimpleDi::singleton();
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
