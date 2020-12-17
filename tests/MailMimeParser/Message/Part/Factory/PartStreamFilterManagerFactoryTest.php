<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;

/**
 * PartStreamFilterManagerFactoryTest
 *
 * @group PartStreamFilterManagerFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Parser\Part\PartStreamFilterManagerFactory
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerFactoryTest extends TestCase
{
    protected $partStreamFilterManagerFactory;

    protected function legacySetUp()
    {
        $mocksdf = $this->getMockBuilder('ZBateson\MailMimeParser\Stream\StreamFactory')
            ->getMock();
        $this->partStreamFilterManagerFactory = new PartStreamFilterManagerFactory(
            $mocksdf
        );
    }

    public function testNewInstance()
    {
        $manager = $this->partStreamFilterManagerFactory->newInstance();
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\PartStreamFilterManager',
            $manager
        );
    }
}
