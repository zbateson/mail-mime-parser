<?php

namespace ZBateson\MailMimeParser\IntegrationTests;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * Verifies that the parser bounds resource use on hostile input and records an
 * error when a limit is reached.
 *
 * @group Parser
 * @group Base
 * @author Zaahid Bateson
 */
class ParsingLimitsIntegrationTest extends TestCase
{
    private function nestedMessage(int $depth) : string
    {
        $head = '';
        $tail = '';
        for ($i = 0; $i < $depth; $i++) {
            $head .= "Content-Type: multipart/mixed; boundary=b$i\r\n\r\n--b$i\r\n";
            $tail = "--b$i--\r\n" . $tail;
        }
        return $head . "Content-Type: text/plain\r\n\r\nx\r\n" . $tail;
    }

    public function testNestingBeyondMaxDepthRecordsError() : void
    {
        $message = (new MailMimeParser())->parse($this->nestedMessage(300), false);
        $found = false;
        foreach ($message->getAllErrors() as $e) {
            if (\str_contains($e->getMessage(), 'nesting depth')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'expected a max nesting depth error to be recorded');
    }

    public function testManyHeadersRecordsError() : void
    {
        $raw = "From: a@b\r\n" . \str_repeat("X-H: v\r\n", 5000) . "\r\nbody\r\n";
        $message = (new MailMimeParser())->parse($raw, false);
        $found = false;
        foreach ($message->getAllErrors() as $e) {
            if (\str_contains($e->getMessage(), 'Header count or total size limit')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'expected a header limit error to be recorded');
    }
}
