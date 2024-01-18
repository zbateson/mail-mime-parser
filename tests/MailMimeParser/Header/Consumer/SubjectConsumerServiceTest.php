<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;

/**
 * Description of SubjectConsumerServiceTest
 *
 * @group Consumers
 * @group SubjectConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\SubjectConsumerService
 * @covers ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService
 * @author Zaahid Bateson
 */
class SubjectConsumerServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $subjectConsumer;

    protected function setUp() : void
    {
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->setMethods(['__toString'])
            ->getMock();
        $mlpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory::class)
            ->setConstructorArgs([$charsetConverter])
            ->setMethods(['__toString'])
            ->getMock();
        $this->subjectConsumer = new SubjectConsumerService($mlpf);
    }

    public function testConsumeTokens() : void
    {
        $value = "Je\ \t suis\nici";

        $ret = $this->subjectConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals("Je\ \t suis ici", $ret[0]);
    }

    public function testFilterSpacesBetweenMimeParts() : void
    {
        $value = "=?US-ASCII?Q?Je?=    =?US-ASCII?Q?suis?=\n=?US-ASCII?Q?ici?=";

        $ret = $this->subjectConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Jesuisici', $ret[0]);
    }
}
