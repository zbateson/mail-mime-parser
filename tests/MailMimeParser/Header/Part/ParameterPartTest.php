<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ParameterTest
 *
 * @group HeaderParts
 * @group ParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\ParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class ParameterPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;
    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
    }

    private function getToken(string $value) : Token
    {
        return $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->logger, $this->mb, $value])
            ->setMethods()
            ->getMock();
    }

    private function getContainerPart(string $value) : ContainerPart
    {
        return $this->getMockBuilder(ContainerPart::class)
            ->setConstructorArgs([$this->logger, $this->mb, [$this->getToken($value)]])
            ->setMethods()
            ->getMock();
    }

    private function assertNameValue($expectedName, $expectedValue, $actualName = null, $actualValue = null) : ParameterPart
    {
        if ($actualName === null) {
            $actualName = $expectedName;
        }
        if ($actualValue === null) {
            $actualValue = $expectedValue;
        }
        $part = new ParameterPart(
            $this->logger,
            $this->mb,
            [$this->getToken($actualName)],
            $this->getContainerPart($actualValue)
        );
        $this->assertEquals($expectedName, $part->getName());
        $this->assertEquals($expectedValue, $part->getValue());
        return $part;
    }

    public function testBasicNameValuePair() : void
    {
        $this->assertNameValue('Name', 'Value');
    }

    public function testEncodedValues() : void
    {
        $this->assertNameValue('name', 'Khal Drogo', 'name*', 'Khal%20Drogo');
        $this->assertNameValue('name', 'Khal Drogo', 'name*', '\'\'Khal%20Drogo');
    }

    public function testEncodedValueWithCharset() : void
    {
        $part = $this->assertNameValue('name', 'Khal Drogo', 'name*', 'UTF-8\'\'Khal%20Drogo');
        $this->assertEquals('UTF-8', $part->getCharset());
    }

    public function testGetLanguage() : void
    {
        $part = $this->assertNameValue('name', 'Khal Drogo', 'name*', '\'en-CA\'Khal%20Drogo');
        $this->assertNull($part->getCharset());
        $this->assertEquals('en-CA', $part->getLanguage());
        $part = $this->assertNameValue('name', 'Khal Drogo', 'name*', 'UTF-8\'en-CA\'Khal%20Drogo');
        $this->assertEquals('UTF-8', $part->getCharset());
        $this->assertEquals('en-CA', $part->getLanguage());
    }

    public function testValidation() : void
    {
        $part = $this->assertNameValue('name', '');
        $errs = $part->getErrors(true, LogLevel::NOTICE);
        $this->assertCount(1, $errs);
        $this->assertEquals('NameValuePart value is empty', $errs[0]->getMessage());
        $this->assertEquals(LogLevel::NOTICE, $errs[0]->getPsrLevel());
    }
}
