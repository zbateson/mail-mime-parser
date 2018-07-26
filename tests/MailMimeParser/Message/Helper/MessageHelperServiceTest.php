<?php
namespace ZBateson\MailMimeParser\Message\Helper;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\SimpleDi;

/**
 * MessageHelperServiceTest
 * 
 * @group MessageHelperService
 * @group MessageHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\MessageHelperService
 * @author Zaahid Bateson
 */
class MessageHelperServiceTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $di = SimpleDi::singleton();
        $messageHelperService = $di->getMessageHelperService();

        $genericHelper = $messageHelperService->getGenericHelper();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Helper\GenericHelper', $genericHelper);

        $multipartHelper = $messageHelperService->getMultipartHelper();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Helper\MultipartHelper', $multipartHelper);

        $privacyHelper = $messageHelperService->getPrivacyHelper();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper', $privacyHelper);
    }
}
