<?php
namespace ZBateson\MailMimeParser;

use LegacyPHPUnit\TestCase;

/**
 * Description of DefaultProviderTest
 *
 * @group DefaultProvider
 * @group Base
 * @covers ZBateson\MailMimeParser\DefaultProvider
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

        $mockDi->expects($this->exactly(3))
            ->method('factory')
            ->willReturn('toast');

        $mockDi->expects($this->exactly(3))
            ->method('offsetSet')
            ->withConsecutive(
                [ 'ZBateson\MailMimeParser\Message\PartStreamContainer', 'toast' ],
                [ 'ZBateson\MailMimeParser\Message\PartHeaderContainer', 'toast' ],
                [ 'ZBateson\MailMimeParser\Message\PartChildrenContainer', 'toast' ]
            );

        $this->provider->register($mockDi);
        $this->assertNotNull($mockDi);
    }
}
