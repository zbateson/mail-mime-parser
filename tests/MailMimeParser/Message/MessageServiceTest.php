<?php

namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use org\bovigo\vfs\vfsStream;
use Exception;

/**
 * MessageServiceTest
 *
 * @group MessageService
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\MessageService
 * @author Zaahid Bateson
 */
class MessageServiceTest extends TestCase {

    protected $instance;

    protected function legacySetUp()
    {
        $this->instance = new MessageService(
            $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\GenericHelper')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\MultipartHelper')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper')->disableOriginalConstructor()->getMock()
        );
    }

    public function testGetGenericHelper()
    {
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Message\Helper\GenericHelper', $this->instance->getGenericHelper());
    }

    public function testGetMultipartHelper()
    {
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Message\Helper\MultipartHelper', $this->instance->getMultipartHelper());
    }

    public function testGetPrivacyHelper()
    {
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Message\Helper\PrivacyHelper', $this->instance->getPrivacyHelper());
    }
}
