<?php
namespace ZBateson\MailMimeParser;

use LegacyPHPUnit\TestCase;

/**
 * Description of DefaultProviderTest
 *
 * @group DefaultProvider
 * @group Base
 * @covers ZBateson\MailMimeParser\Container
 * @author Zaahid Bateson
 */
class DefaultProviderTest extends TestCase
{
    private $provider;
    
    protected function legacySetUp()
    {
        $this->provider = new DefaultProvider();
    }
    
    public function testRegister()
    {
        $mockDi = $this->getMockBuilder('ZBateson\MailMimeParser\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $mockParser = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\BaseParser')
            ->disableOriginalConstructor()
            ->getMock();

        $mockMimeContentParser = $this->getMockBuilder('\ZBateson\MailMimeParser\Parser\MimeContentParser')
            ->disableOriginalConstructor()
            ->getMock();
        $mockNonMimeParser = $this->getMockBuilder('\ZBateson\MailMimeParser\Parser\NonMimeParser')
            ->disableOriginalConstructor()
            ->getMock();
        $mockChildParser = $this->getMockBuilder('\ZBateson\MailMimeParser\Parser\MultipartChildrenParser')
            ->disableOriginalConstructor()
            ->getMock();

        $mockDi
            ->expects($this->atLeastOnce())
            ->method('offsetGet')
            ->willReturnMap([
                [ '\ZBateson\MailMimeParser\Parser\BaseParser', $mockParser ],
                [ '\ZBateson\MailMimeParser\Parser\MimeContentParser', $mockMimeContentParser ],
                [ '\ZBateson\MailMimeParser\Parser\NonMimeParser', $mockNonMimeParser ],
                [ '\ZBateson\MailMimeParser\Parser\MultipartChildrenParser', $mockChildParser ]
            ]);

        $mockParser
            ->expects($this->exactly(2))
            ->method('addContentParser')
            ->withConsecutive([ $mockMimeContentParser ], [ $mockNonMimeParser ]);
        $mockParser
            ->expects($this->exactly(2))
            ->method('addChildParser')
            ->withConsecutive([ $mockChildParser ], [ $mockNonMimeParser ]);

        $this->provider->register($mockDi);
    }
}
