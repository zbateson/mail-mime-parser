<?php

namespace ZBateson\MailMimeParser;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of ErrorTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(Error::class)]
#[Group('ErrorClass')]
#[Group('Base')]
class ErrorTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $errorBagMock;

    protected function setUp() : void
    {
        $this->errorBagMock = $this->getMockForAbstractClass(ErrorBag::class, [\mmpGetTestLogger()]);
    }

    public function testGetMessage() : void
    {
        $msg = 'Testacular';
        $ob = new Error($msg, LogLevel::ERROR, $this->errorBagMock);
        $this->assertSame($msg, $ob->getMessage());
    }

    public function testGetPsrLevel() : void
    {
        $ll = LogLevel::DEBUG;
        $ob = new Error('', $ll, $this->errorBagMock);
        $this->assertSame($ll, $ob->getPsrLevel());
    }

    public function testConstructorInvalidArgumentExceptionForBadPsrLevel() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new Error('', 'test', $this->errorBagMock);
    }

    public function testGetObjectAndClass() : void
    {
        $ob = new Error('', LogLevel::DEBUG, $this->errorBagMock);
        $this->assertSame(\get_class($this->errorBagMock), $ob->getClass());
        $this->assertSame($this->errorBagMock, $ob->getObject());
    }

    public function testIsPsrLevelGreaterOrEqualTo() : void
    {
        $ob = new Error('', LogLevel::NOTICE, $this->errorBagMock);
        $this->assertTrue($ob->isPsrLevelGreaterOrEqualTo(LogLevel::NOTICE));
        $this->assertTrue($ob->isPsrLevelGreaterOrEqualTo(LogLevel::INFO));
        $this->assertFalse($ob->isPsrLevelGreaterOrEqualTo(LogLevel::ERROR));
    }

    public function testIsPsrLevelGreaterOrEqualToNonPsrLevel() : void
    {
        $ob = new Error('', LogLevel::NOTICE, $this->errorBagMock);
        $this->assertTrue($ob->isPsrLevelGreaterOrEqualTo('baaaaad'));
    }
}
