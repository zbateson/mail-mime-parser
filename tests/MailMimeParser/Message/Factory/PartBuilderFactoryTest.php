<?php
namespace ZBateson\MailMimeParser\Parser\Part;

use LegacyPHPUnit\TestCase;

/**
 * PartBuilderFactoryTest
 *
 * @group PartBuilderFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Parser\PartBuilderFactory
 * @author Zaahid Bateson
 */
class PartBuilderFactoryTest extends TestCase
{
    protected $partBuilderFactory;

    protected function legacySetUp()
    {
        $mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $this->partBuilderFactory = new PartBuilderFactory($mockHeaderFactory, 'amazon');
    }

    public function testNewInstance()
    {
        $mockMessagePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Parser\Part\MessagePartFactory')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $partBuilder = $this->partBuilderFactory->newPartBuilder($mockMessagePartFactory);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Parser\PartBuilder',
            $partBuilder
        );
    }
}
