<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(HeaderBudget::class)]
#[Group('Message')]
#[Group('HeaderBudget')]
class HeaderBudgetTest extends TestCase
{
    public function testStartsWithMaximumsRemaining() : void
    {
        $budget = new HeaderBudget(10, 20);
        $this->assertSame(10, $budget->getMaxTokenCount());
        $this->assertSame(10, $budget->getRemainingTokens());
        $this->assertSame(20, $budget->getMaxHeaderCount());
        $this->assertSame(20, $budget->getRemainingHeaders());
    }

    public function testConsumeReducesRemainingIndependently() : void
    {
        $budget = new HeaderBudget(10, 20);
        $budget->consumeTokens(3)->consumeTokens(4);
        $budget->consumeHeaders(5);
        $this->assertSame(3, $budget->getRemainingTokens());
        $this->assertSame(15, $budget->getRemainingHeaders());
        $this->assertSame(10, $budget->getMaxTokenCount());
        $this->assertSame(20, $budget->getMaxHeaderCount());
    }

    public function testRemainingDoesNotGoBelowZero() : void
    {
        $budget = new HeaderBudget(5, 5);
        $budget->consumeTokens(8)->consumeHeaders(8);
        $this->assertSame(0, $budget->getRemainingTokens());
        $this->assertSame(0, $budget->getRemainingHeaders());
    }
}
