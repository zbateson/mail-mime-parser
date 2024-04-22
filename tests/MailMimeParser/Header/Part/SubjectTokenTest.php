<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of SubjectTokenTest
 *
 * @group HeaderParts
 * @group SubjectToken
 * @covers ZBateson\MailMimeParser\Header\Part\SubjectToken
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class SubjectTokenTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mb;

    protected function setUp() : void
    {
        $this->mb = new MbWrapper();
    }

    private function newSubjectToken($value)
    {
        return new SubjectToken(\mmpGetTestLogger(), $this->mb, $value);
    }

    public function testInstance() : void
    {
        $token = $this->newSubjectToken('testing');
        $this->assertNotNull($token);
        $this->assertEquals('testing', $token->getValue());
        $this->assertEquals('testing', (string) $token);
    }

    public function testNewLines() : void
    {
        $token = $this->newSubjectToken("tes\n\tting");
        $this->assertNotNull($token);
        $this->assertEquals("tes\tting", $token->getValue());
    }

    public function testNewLinesTabAndSpace() : void
    {
        $token = $this->newSubjectToken("tes\n\t ting");
        $this->assertNotNull($token);
        $this->assertEquals("tes\tting", $token->getValue());
    }
}
