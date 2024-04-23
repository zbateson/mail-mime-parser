<?php

namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\TestCase;
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
    private function newErrorBagStub($children = [])
    {
        $stub = $this->getMockBuilder('\\' . ErrorBag::class)
            ->setConstructorArgs([\mmpGetTestLogger()])
            ->getMockForAbstractClass();
        $stub->method('getErrorBagChildren')
            ->willReturn($children);
        return $stub;
    }

    public function testInstance() : void
    {
        $ob = $this->newErrorBagStub();
        $this->assertInstanceOf(ErrorBag::class, $ob);
        $this->assertInstanceOf(IErrorBag::class, $ob);
    }

    public function testGetErrorLoggingContextName() : void
    {
        $ob = $this->newErrorBagStub();
        $this->assertEquals(\get_class($ob), $ob->getErrorLoggingContextName());
    }

    public function testAddHasAndGetErrors() : void
    {
        $ob = $this->newErrorBagStub();
        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();

        $this->assertFalse($ob->hasErrors());
        $this->assertSame([], $ob->getErrors());

        $this->assertSame($ob, $ob->addError('test1', LogLevel::ERROR));
        $this->assertSame($ob, $ob->addError('test2', LogLevel::ERROR));

        $this->assertTrue($ob->hasErrors());
        $errors = $ob->getErrors();
        $this->assertCount(2, $errors);
        $this->assertSame('test1', $errors[0]->getMessage());
        $this->assertSame('test2', $errors[1]->getMessage());
        $this->assertSame($ob, $errors[0]->getObject());
        $this->assertSame($ob, $errors[1]->getObject());
        $this->assertSame(LogLevel::ERROR, $errors[0]->getPsrLevel());
        $this->assertSame(LogLevel::ERROR, $errors[1]->getPsrLevel());
    }

    public function testGetHasFilteredErrors() : void
    {
        $ob = $this->newErrorBagStub();
        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();

        $this->assertSame($ob, $ob->addError('test1', LogLevel::ERROR));
        $this->assertSame($ob, $ob->addError('test2', LogLevel::NOTICE));

        $this->assertTrue($ob->hasErrors());
        $errors = $ob->getErrors();

        $this->assertCount(1, $errors);
        $this->assertSame('test1', $errors[0]->getMessage());
        $this->assertSame($ob, $errors[0]->getObject());
        $this->assertSame(LogLevel::ERROR, $errors[0]->getPsrLevel());

        $this->assertFalse($ob->hasErrors(false, LogLevel::CRITICAL));
        $this->assertCount(0, $ob->getErrors(false, LogLevel::CRITICAL));

        $this->assertTrue($ob->hasErrors(false, LogLevel::NOTICE));
        $errors = $ob->getErrors(false, LogLevel::NOTICE);
        $this->assertCount(2, $errors);
        $this->assertSame('test1', $errors[0]->getMessage());
        $this->assertSame('test2', $errors[1]->getMessage());
        $this->assertSame($ob, $errors[0]->getObject());
        $this->assertSame($ob, $errors[1]->getObject());
        $this->assertSame(LogLevel::ERROR, $errors[0]->getPsrLevel());
        $this->assertSame(LogLevel::NOTICE, $errors[1]->getPsrLevel());
    }

    public function testAddHasAnyAndGetAllErrors() : void
    {
        $subChild = $this->newErrorBagStub();
        $child = $this->newErrorBagStub([$subChild]);
        $child2 = $this->newErrorBagStub();
        $ob = $this->newErrorBagStub([$child, $child2]);

        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();

        $this->assertFalse($ob->hasAnyErrors());
        $this->assertSame([], $ob->getAllErrors());

        $this->assertSame($ob, $ob->addError('test1', LogLevel::ERROR));
        $this->assertSame($ob, $ob->addError('test2', LogLevel::ERROR));
        $this->assertSame($child, $child->addError('child1', LogLevel::ERROR));
        $this->assertSame($child, $child->addError('child2', LogLevel::ERROR));
        $this->assertSame($subChild, $subChild->addError('subchild1', LogLevel::ERROR));
        $this->assertSame($subChild, $subChild->addError('subchild2', LogLevel::ERROR));
        $this->assertSame($child2, $child2->addError('schild1', LogLevel::ERROR));
        $this->assertSame($child2, $child2->addError('schild2', LogLevel::ERROR));

        $this->assertTrue($ob->hasAnyErrors());
        $errors = $ob->getAllErrors();
        $this->assertCount(8, $errors);
        $this->assertSame('test1', $errors[0]->getMessage());
        $this->assertSame('test2', $errors[1]->getMessage());
        $this->assertSame('child1', $errors[2]->getMessage());
        $this->assertSame('child2', $errors[3]->getMessage());
        $this->assertSame('subchild1', $errors[4]->getMessage());
        $this->assertSame('subchild2', $errors[5]->getMessage());
        $this->assertSame('schild1', $errors[6]->getMessage());
        $this->assertSame('schild2', $errors[7]->getMessage());
    }

    public function testAddHasAnyAndGetAllFilteredErrors() : void
    {
        $subChild = $this->newErrorBagStub();
        $child = $this->newErrorBagStub([$subChild]);
        $child2 = $this->newErrorBagStub();
        $ob = $this->newErrorBagStub([$child, $child2]);

        $mb = $this->getMockBuilder('\\' . Error::class)
            ->disableOriginalConstructor();
        $this->assertFalse($ob->hasAnyErrors());
        $this->assertSame([], $ob->getAllErrors());

        $this->assertSame($ob, $ob->addError('test1', LogLevel::ERROR));
        $this->assertSame($ob, $ob->addError('test2', LogLevel::INFO));
        $this->assertSame($child, $child->addError('child1', LogLevel::ERROR));
        $this->assertSame($child, $child->addError('child2', LogLevel::INFO));
        $this->assertSame($subChild, $subChild->addError('subchild1', LogLevel::ERROR));
        $this->assertSame($subChild, $subChild->addError('subchild2', LogLevel::INFO));
        $this->assertSame($child2, $child2->addError('schild1', LogLevel::ERROR));
        $this->assertSame($child2, $child2->addError('schild2', LogLevel::INFO));

        $this->assertTrue($ob->hasAnyErrors());
        $errors = $ob->getAllErrors();
        $this->assertCount(4, $errors);
        $this->assertSame('test1', $errors[0]->getMessage());
        $this->assertSame('child1', $errors[1]->getMessage());
        $this->assertSame('subchild1', $errors[2]->getMessage());
        $this->assertSame('schild1', $errors[3]->getMessage());

        $this->assertTrue($ob->hasAnyErrors(false, LogLevel::INFO));
        $errors = $ob->getAllErrors(false, LogLevel::INFO);
        $this->assertCount(8, $errors);
        $this->assertSame('test1', $errors[0]->getMessage());
        $this->assertSame('test2', $errors[1]->getMessage());
        $this->assertSame('child1', $errors[2]->getMessage());
        $this->assertSame('child2', $errors[3]->getMessage());
        $this->assertSame('subchild1', $errors[4]->getMessage());
        $this->assertSame('subchild2', $errors[5]->getMessage());
        $this->assertSame('schild1', $errors[6]->getMessage());
        $this->assertSame('schild2', $errors[7]->getMessage());
    }
}
