<?php
namespace ZBateson\MailMimeParser\Message\Part\Factory;

use PHPUnit\Framework\TestCase;

/**
 * PartBuilderFactoryTest
 *
 * @group PartBuilderFactory
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory
 * @author Zaahid Bateson
 */
class PartBuilderFactoryTest extends TestCase
{
    protected $partBuilderFactory;

    protected function setUp()
    {
        $mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
        $this->partBuilderFactory = new PartBuilderFactory($mockHeaderFactory, 'amazon');
    }

    public function testNewInstance()
    {
        $mockMessagePartFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\Factory\MessagePartFactory')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $partBuilder = $this->partBuilderFactory->newPartBuilder($mockMessagePartFactory);
        $this->assertInstanceOf(
            '\ZBateson\MailMimeParser\Message\Part\PartBuilder',
            $partBuilder
        );
    }
}
