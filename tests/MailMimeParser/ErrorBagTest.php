<?php

namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;

/**
 * Description of MessageTest
 *
 * @group ErrorBag
 * @group Base
 * @covers ZBateson\MailMimeParser\ErrorBag
 * @author Zaahid Bateson
 */
class ErrorBagTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockLogger = $this->getMockBuilder('\\' . NullLogger::class)
            ->getMock();
    }

    private function newErrorBagStub($children = [])
    {
        $stub = $this->getMockBuilder('\\' . ErrorBag::class)
            ->getMockForAbstractClass();
        $stub->method('getErrorBagChildren')
            ->willReturn($children);
        $stub->setLogger($this->mockLogger);
        return $stub;
    }

    public function testInstance() : void
    {
        $ob = $this->newErrorBagStub();
        $this->assertInstanceOf(ErrorBag::class, $ob);
        $this->assertInstanceOf(IErrorBag::class, $ob);
        $this->assertInstanceOf(ILogger::class, $ob);
        $this->assertInstanceOf(Logger::class, $ob);
    }

    public function testGetErrorLoggingContextName() : void
    {
        $ob = $this->newErrorBagStub();
        $this->assertEquals(get_class($ob), $ob->getErrorLoggingContextName());
    }

    public function testAddHasAndGetErrors() : void
    {
        $ob = $this->newErrorBagStub();
        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();
        $errors = [
            $mb->getMock(),
            $mb->getMock()
        ];

        $errors[0]->expects($this->once())->method('getPsrLevel')->willReturn(LogLevel::ERROR);
        $errors[0]->expects($this->once())->method('getMessage')->willReturn('test1');
        $errors[1]->expects($this->once())->method('getPsrLevel')->willReturn(LogLevel::ERROR);
        $errors[1]->expects($this->once())->method('getMessage')->willReturn('test2');

        $this->assertFalse($ob->hasErrors());
        $this->assertSame([], $ob->getErrors());

        $this->assertSame($ob, $ob->addError($errors[0]));
        $this->assertSame($ob, $ob->addError($errors[1]));

        $errors[0]->expects($this->exactly(2))->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::ERROR)->willReturn(true);
        $errors[1]->expects($this->exactly(2))->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::ERROR)->willReturn(true);

        $this->assertTrue($ob->hasErrors());
        $this->assertSame($errors, $ob->getErrors());
    }

    public function testGetHasFilteredErrors() : void
    {
        $ob = $this->newErrorBagStub();
        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();
        $errors = [
            $mb->getMock(),
            $mb->getMock()
        ];

        $this->assertFalse($ob->hasErrors(true, LogLevel::INFO));
        $this->assertSame([], $ob->getErrors(true, LogLevel::INFO));

        $this->assertSame($ob, $ob->addError($errors[0]));
        $this->assertSame($ob, $ob->addError($errors[1]));

        $errors[0]->expects($this->exactly(2))->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::INFO)->willReturn(true);
        $errors[1]->expects($this->exactly(2))->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::INFO)->willReturn(false);

        $this->assertTrue($ob->hasErrors(true, LogLevel::INFO));
        $this->assertSame([ $errors[0] ], $ob->getErrors(true, LogLevel::INFO));
    }

    public function testAddHasAnyAndGetAllErrors() : void
    {
        $subChild = $this->newErrorBagStub();
        $child = $this->newErrorBagStub([$subChild]);
        $child2 = $this->newErrorBagStub();
        $ob = $this->newErrorBagStub([$child, $child2]);

        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();
        $errors = [
            $mb->getMock(),
            $mb->getMock()
        ];
        
        $errors[0]->expects($this->any())->method('getPsrLevel')->willReturn(LogLevel::ERROR);
        $errors[0]->expects($this->any())->method('getMessage')->willReturn('test1');
        $errors[1]->expects($this->any())->method('getPsrLevel')->willReturn(LogLevel::ERROR);
        $errors[1]->expects($this->any())->method('getMessage')->willReturn('test2');

        $this->assertFalse($ob->hasAnyErrors());
        $this->assertSame([], $ob->getAllErrors());

        $this->assertSame($ob, $ob->addError($errors[0]));
        $this->assertSame($ob, $ob->addError($errors[1]));
        $this->assertSame($child, $child->addError($errors[0]));
        $this->assertSame($child, $child->addError($errors[1]));
        $this->assertSame($subChild, $subChild->addError($errors[0]));
        $this->assertSame($subChild, $subChild->addError($errors[1]));
        $this->assertSame($child2, $child2->addError($errors[0]));
        $this->assertSame($child2, $child2->addError($errors[1]));

        $errors[0]->expects($this->any())->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::ERROR)->willReturn(true);
        $errors[1]->expects($this->any())->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::ERROR)->willReturn(true);

        $this->assertTrue($ob->hasAnyErrors());
        $this->assertCount(8, $ob->getAllErrors());
        $this->assertSame([ $errors[0], $errors[1], $errors[0], $errors[1], $errors[0], $errors[1], $errors[0], $errors[1] ], $ob->getAllErrors());
    }

    public function testAddHasAnyAndGetAllFilteredErrors() : void
    {
        $subChild = $this->newErrorBagStub();
        $child = $this->newErrorBagStub([$subChild]);
        $child2 = $this->newErrorBagStub();
        $ob = $this->newErrorBagStub([$child, $child2]);

        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();
        $errors = [
            $mb->getMock(),
            $mb->getMock()
        ];

        $this->assertFalse($ob->hasAnyErrors(true, LogLevel::INFO));
        $this->assertSame([], $ob->getAllErrors(true, LogLevel::INFO));

        $errors[0]->expects($this->any())->method('getPsrLevel')->willReturn(LogLevel::ERROR);
        $errors[0]->expects($this->any())->method('getMessage')->willReturn('test1');
        $errors[1]->expects($this->any())->method('getPsrLevel')->willReturn(LogLevel::ERROR);
        $errors[1]->expects($this->any())->method('getMessage')->willReturn('test2');

        $this->assertSame($ob, $ob->addError($errors[0]));
        $this->assertSame($ob, $ob->addError($errors[1]));
        $this->assertSame($child, $child->addError($errors[0]));
        $this->assertSame($child, $child->addError($errors[1]));
        $this->assertSame($subChild, $subChild->addError($errors[0]));
        $this->assertSame($subChild, $subChild->addError($errors[1]));
        $this->assertSame($child2, $child2->addError($errors[0]));
        $this->assertSame($child2, $child2->addError($errors[1]));

        $errors[0]->expects($this->any())->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::INFO)->willReturn(false);
        $errors[1]->expects($this->any())->method('isPsrLevelGreaterOrEqualTo')->with(LogLevel::INFO)->willReturn(true);

        $this->assertTrue($ob->hasAnyErrors(true, LogLevel::INFO));
        $this->assertCount(4, $ob->getAllErrors(true, LogLevel::INFO));
        $this->assertSame([ $errors[1], $errors[1], $errors[1], $errors[1] ], $ob->getAllErrors(true, LogLevel::INFO));
    }
}
