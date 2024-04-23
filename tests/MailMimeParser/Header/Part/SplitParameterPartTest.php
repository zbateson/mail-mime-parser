<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of ParameterTest
 *
 * @group HeaderParts
 * @group SplitParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\SplitParameterPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class SplitParameterPartTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $logger;

    private $mb;

    private $hpf;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $this->mb = new MbWrapper();
        $this->hpf = $this->getMockBuilder(HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $this->mb])
            ->setMethods()
            ->getMock();
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

    private function assertNameValue(string $expectedName, string $expectedValue, string|array|null $actualNames = null, string|array|null $actualValues = null) : SplitParameterPart
    {
        if ($actualNames === null) {
            $actualNames = $expectedName;
        }
        if ($actualValues === null) {
            $actualValues = [$expectedValue];
        }
        if (!\is_array($actualValues)) {
            $actualValues = [$actualValues];
        }
        if (!\is_array($actualNames)) {
            $actualNames = [$actualNames];
        }
        if (\count($actualNames) < \count($actualValues)) {
            $actualNames = \array_fill(\count($actualNames), \count($actualValues), $expectedName);
        }

        $mapped = \array_map(
            fn ($arr) => new ParameterPart($this->logger, $this->mb, [$this->getToken($arr[0])], $this->getContainerPart($arr[1])),
            \array_map(null, $actualNames, $actualValues)
        );

        $part = new SplitParameterPart($this->logger, $this->mb, $this->hpf, $mapped);
        $this->assertEquals($expectedName, $part->getName());
        $this->assertEquals($expectedValue, $part->getValue());
        return $part;
    }

    public function testBasicNameValuePair() : void
    {
        $this->assertNameValue('Name', 'Value');
    }

    public function testEncodedPart() : void
    {
        $part = $this->assertNameValue('Name', 'blah', 'Name*', 'utf-8\'Dothraki\'blah');
        $this->assertEquals('Dothraki', $part->getLanguage());
    }

    public function testLanguageIsNullForEmptyEncodedLanguage() : void
    {
        $part = $this->assertNameValue('Name', 'blah', 'Name*', 'iso-8859-1\'\'blah');
        $this->assertNull($part->getLanguage());
    }

    public function testCombiningMultipleParts() : void
    {
        $this->assertNameValue('Name', 'seems really very good', 'Name*', ['seems ', 'really', ' very ', 'good']);
    }

    public function testCombiningMultipleEncodedParts() : void
    {
        $this->assertNameValue('header', 'encoded/not encoded parts all%20mixed together', ['header*0*', 'header*1', 'header*2*'], ['encoded%2Fnot%20encoded parts%20', 'all%20', 'mixed%20together']);
        $this->assertNameValue('header', 'encoded/not encoded parts all mixed together', ['header*1*', 'header*0*', 'header*2*'], ['all%20', 'encoded%2Fnot%20encoded parts%20', 'mixed%20together']);
    }

    public function testEncodingOnContinuingParts() : void
    {
        $part = $this->assertNameValue('header', 'هلا هلا شخبار؟', ['header*0*', 'header*1*', 'header*2*'], ['utf-8\'ar-bh\'%D9%87%D9%84%D8%A7%20', '%D9%87%D9%84%D8%A7%20', '%D8%B4%D8%AE%D8%A8%D8%A7%D8%B1%D8%9F']);
        $this->assertEquals('utf-8', $part->getCharset());
        $this->assertEquals('ar-bh', $part->getLanguage());
        $part = $this->assertNameValue('header', 'دنت كبتن والله', ['header*0*', 'header*1*', 'header*2*', 'header*3*'], ['CP1256\'ar-eg\'%CF%E4%CA%20', '%DF%C8%CA%E4', '%20', '%E6%C7%E1%E1%E5']);
        $this->assertEquals('CP1256', $part->getCharset());
        $this->assertEquals('ar-eg', $part->getLanguage());
        $part = $this->assertNameValue('header', 'دنت كبتن والله', ['header*3*', 'header*1*', 'header*2*', 'header*0*'], ['%E6%C7%E1%E1%E5', '%DF%C8%CA%E4', '%20', 'CP1256\'ar-eg\'%CF%E4%CA%20']);
        $this->assertEquals('CP1256', $part->getCharset());
        $this->assertEquals('ar-eg', $part->getLanguage());
    }

    public function testDifferentEncodingOnContinuedPart() : void
    {
        $part = $this->assertNameValue(
            'header',
            'هلا دنت كبتن شخبار؟',
            ['header*0*', 'header*1*', 'header*2*', 'header*2*'],
            ['utf-8\'ar-bh\'%D9%87%D9%84%D8%A7%20', 'CP1256\'ar-eg\'%CF%E4%CA%20', 'CP1256\'ar-eg\'%DF%C8%CA%E4%20', '%D8%B4%D8%AE%D8%A8%D8%A7%D8%B1%D8%9F']
        );
        $this->assertEquals('utf-8', $part->getCharset());
        $this->assertEquals('ar-bh', $part->getLanguage());
        $children = $part->getChildParts();
        $this->assertCount(4, $children);
        $this->assertEquals('CP1256', $children[1]->getCharset());
        $this->assertEquals('ar-eg', $children[1]->getLanguage());
    }

    public function testErrorOnUnsupportedCharset() : void
    {
        $part = $this->assertNameValue('header', 'seems good', ['header*0*', 'header*1*'], ['unknown\'\'seems%20', 'good']);
        $errs = $part->getAllErrors();
        $this->assertCount(2, $errs);
        $err = $errs[0];
        $this->assertSame($part, $err->getObject());
        $this->assertInstanceOf(\ZBateson\MbWrapper\UnsupportedCharsetException::class, $err->getException());
        $this->assertInstanceOf(\ZBateson\MbWrapper\UnsupportedCharsetException::class, $errs[1]->getException());
    }
}
