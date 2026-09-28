<?php

namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;

/**
 * @group Message
 * @group HeaderBudget
 * @covers ZBateson\MailMimeParser\Message\HeaderBudget
 */
class HeaderBudgetTest extends TestCase
{
    public function testStartsWithMaximumsRemaining() : void
    {
        $budget = new HeaderBudget(10, 20, 30);
        $this->assertSame(10, $budget->getMaxTokenCount());
        $this->assertSame(10, $budget->getRemainingTokens());
        $this->assertSame(20, $budget->getMaxHeaderCount());
        $this->assertSame(20, $budget->getRemainingHeaders());
        $this->assertSame(30, $budget->getMaxSizeBytes());
        $this->assertSame(30, $budget->getRemainingBytes());
    }

    public function testConsumeReducesRemainingIndependently() : void
    {
        $budget = new HeaderBudget(10, 20, 30);
        $budget->consumeTokens(3)->consumeTokens(4);
        $budget->consumeHeaders(5);
        $budget->consumeBytes(6);
        $this->assertSame(3, $budget->getRemainingTokens());
        $this->assertSame(15, $budget->getRemainingHeaders());
        $this->assertSame(24, $budget->getRemainingBytes());
        $this->assertSame(10, $budget->getMaxTokenCount());
        $this->assertSame(20, $budget->getMaxHeaderCount());
        $this->assertSame(30, $budget->getMaxSizeBytes());
    }

    public function testRemainingDoesNotGoBelowZero() : void
    {
        $budget = new HeaderBudget(5, 5, 5);
        $budget->consumeTokens(8)->consumeHeaders(8)->consumeBytes(8);
        $this->assertSame(0, $budget->getRemainingTokens());
        $this->assertSame(0, $budget->getRemainingHeaders());
        $this->assertSame(0, $budget->getRemainingBytes());
    }
}
