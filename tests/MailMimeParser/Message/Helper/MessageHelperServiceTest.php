<?php
namespace ZBateson\MailMimeParser\Message\Helper;

use LegacyPHPUnit\TestCase;
use ZBateson\MailMimeParser\Container;

/**
 * MessageHelperServiceTest
 *
 * @group MessageHelperService
 * @group MessageHelper
 * @covers ZBateson\MailMimeParser\Message\Helper\MessageHelperService
 * @author Zaahid Bateson
 */
class MessageHelperServiceTest extends TestCase
{
    public function testInstance()
    {
        $di = new Container();
        $messageHelperService = $di->getMessageHelperService();

        $genericHelper = $messageHelperService->getGenericHelper();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Helper\GenericHelper', $genericHelper);

        $multipartHelper = $messageHelperService->getMultipartHelper();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Helper\MultipartHelper', $multipartHelper);

        $privacyHelper = $messageHelperService->getPrivacyHelper();
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Helper\PrivacyHelper', $privacyHelper);
    }
}
