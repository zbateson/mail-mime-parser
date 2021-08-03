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
        $this->provider->register($mockDi);
        $this->assertNotNull($mockDi);
    }
}
