<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of SubjectTokenTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(SubjectToken::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('SubjectToken')]
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
