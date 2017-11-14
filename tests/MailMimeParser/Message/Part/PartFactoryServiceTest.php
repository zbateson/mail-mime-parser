<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\SimpleDi;

/**
 * PartFactoryServiceTest
 * 
 * @group PartFactoryService
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\PartFactoryService
 * @author Zaahid Bateson
 */
class PartFactoryServiceTest extends PHPUnit_Framework_TestCase
{
    protected $partFactoryService;
    
    protected function setUp()
    {
        $di = SimpleDi::singleton();
        $this->partFactoryService = $di->getPartFactoryService();
    }
    
    public function testInstance()
    {
        $messageFactory = $this->partFactoryService->getMessageFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\MessageFactory', $messageFactory);
        
        $mimePartFactory = $this->partFactoryService->getMimePartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\MimePartFactory', $mimePartFactory);
        
        $nonMimePartFactory = $this->partFactoryService->getNonMimePartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\NonMimePartFactory', $nonMimePartFactory);
        
        $uuEncodedPartFactory = $this->partFactoryService->getUUEncodedPartFactory();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\UUEncodedPartFactory', $uuEncodedPartFactory);
    }
}
