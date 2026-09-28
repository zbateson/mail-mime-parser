<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;

/**
 * @group Message
 * @group HeaderTokenBudget
 * @covers ZBateson\MailMimeParser\Message\HeaderTokenBudget
 */
class HeaderTokenBudgetTest extends TestCase
{
    public function testStartsWithMaxTokenCountRemaining() : void
    {
        $budget = new HeaderTokenBudget(10);
        $this->assertSame(10, $budget->getMaxTokenCount());
        $this->assertSame(10, $budget->getRemaining());
    }

    public function testConsumeReducesRemaining() : void
    {
        $budget = new HeaderTokenBudget(10);
        $budget->consume(3)->consume(4);
        $this->assertSame(3, $budget->getRemaining());
        $this->assertSame(10, $budget->getMaxTokenCount());
    }

    public function testRemainingDoesNotGoBelowZero() : void
    {
        $budget = new HeaderTokenBudget(5);
        $budget->consume(8);
        $this->assertSame(0, $budget->getRemaining());
    }
}
