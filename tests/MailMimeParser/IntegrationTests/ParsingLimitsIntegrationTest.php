<?php

namespace ZBateson\MailMimeParser\IntegrationTests;

use PHPUnit\Framework\TestCase;
use ZBateson\MailMimeParser\IErrorBag;
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

    private function siblingMessage(int $count) : string
    {
        return "Content-Type: multipart/mixed; boundary=b\r\n\r\n"
            . \str_repeat("--b\r\nContent-Type: text/plain\r\n\r\nx\r\n", $count)
            . "--b--\r\n";
    }

    private function uuEncodedMessage(int $count) : string
    {
        return "Subject: test\r\n\r\n" . \str_repeat("begin 644 file.txt\r\nend\r\n", $count);
    }

    private function assertErrorRecorded(IErrorBag $errorBag, string $needle) : void
    {
        foreach ($errorBag->getAllErrors(true) as $e) {
            if (\str_contains($e->getMessage(), $needle)) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail("expected an error containing \"$needle\" to be recorded");
    }

    public function testNestingBeyondMaxDepthRecordsError() : void
    {
        $message = (new MailMimeParser())->parse($this->nestedMessage(300), false);
        $this->assertErrorRecorded($message, 'nesting depth');
    }

    public function testManyHeadersRecordsError() : void
    {
        $raw = "From: a@b\r\n" . \str_repeat("X-H: v\r\n", 5000) . "\r\nbody\r\n";
        $message = (new MailMimeParser())->parse($raw, false);
        $this->assertErrorRecorded($message, 'Header count or total size limit');
    }

    public function testSiblingPartsBeyondMaxCountStopParsingAndRecordError() : void
    {
        $parser = new MailMimeParser(null, ['maxMessagePartCount' => 5]);
        $message = $parser->parse($this->siblingMessage(50), false);
        $this->assertSame(5, $message->getChildCount());
        $this->assertErrorRecorded($message, 'Maximum message part count of 5 reached');
    }

    public function testUUEncodedPartsBeyondMaxCountStopParsingAndRecordError() : void
    {
        $parser = new MailMimeParser(null, ['maxMessagePartCount' => 5]);
        $message = $parser->parse($this->uuEncodedMessage(50), false);
        $this->assertSame(5, $message->getChildCount());
        $this->assertErrorRecorded($message, 'Maximum message part count of 5 reached');
    }

    public function testSiblingPartsUnderMaxCountAreAllParsed() : void
    {
        $parser = new MailMimeParser(null, ['maxMessagePartCount' => 5]);
        $message = $parser->parse($this->siblingMessage(5), false);
        $this->assertSame(5, $message->getChildCount());
        $this->assertEmpty($message->getAllErrors());
    }

    public function testUUEncodedPartsUnderMaxCountAreAllParsed() : void
    {
        $parser = new MailMimeParser(null, ['maxMessagePartCount' => 5]);
        $message = $parser->parse($this->uuEncodedMessage(5), false);
        $this->assertSame(5, $message->getChildCount());
        $this->assertEmpty($message->getAllErrors());
    }

    public function testNestedCommentsWithinMaxDepthArePreserved() : void
    {
        $parser = new MailMimeParser(null, ['maxCommentDepth' => 3]);
        $message = $parser->parse("To: a@b.com (one (two (three)))\r\n\r\nbody\r\n", false);
        $header = $message->getHeader('To');
        $this->assertSame(['one (two (three))'], $header->getComments());
        $this->assertSame('a@b.com', $header->getAddresses()[0]->getEmail());
    }

    public function testCommentsBeyondMaxDepthAreDropped() : void
    {
        $parser = new MailMimeParser(null, ['maxCommentDepth' => 1]);
        $message = $parser->parse("To: a@b.com (one (two (three)))\r\n\r\nbody\r\n", false);
        $header = $message->getHeader('To');
        // the nested comments are parsed over, but not kept
        $this->assertSame(['one'], $header->getComments());
        $this->assertSame('a@b.com', $header->getAddresses()[0]->getEmail());
    }

    public function testDeeplyNestedCommentDoesNotPreventParsingTheHeader() : void
    {
        $nested = \str_repeat('(', 5000) . \str_repeat(')', 5000);
        $raw = 'To: ' . \implode("\r\n ", \str_split($nested, 3900)) . " a\@b.com\r\n\r\nbody\r\n";
        $message = (new MailMimeParser())->parse($raw, false);
        $addresses = $message->getHeader('To')->getAddresses();
        $this->assertCount(1, $addresses);
        $this->assertSame('a@b.com', $addresses[0]->getEmail());
    }

    public function testHeaderValueBeyondMaxTokenCountRecordsError() : void
    {
        $parser = new MailMimeParser(null, ['maxHeaderTokenCount' => 10]);
        $message = $parser->parse("To: " . \rtrim(\str_repeat('a@b.com,', 20), ',') . "\r\n\r\nbody\r\n", false);
        $this->assertErrorRecorded($message->getHeader('To'), 'token limit of 10 reached');
    }

    public function testHeaderValueBeyondMaxTokenCountKeepsTheRemainder() : void
    {
        $value = \trim(\str_repeat('word ', 20));
        $parser = new MailMimeParser(null, ['maxHeaderTokenCount' => 10]);
        $message = $parser->parse("Subject: $value\r\n\r\nbody\r\n", false);
        // past the limit the rest is kept as a single unparsed token, so no
        // content is silently dropped from the value
        $this->assertSame($value, $message->getHeader('Subject')->getValue());
    }

    public function testHeaderValueUnderMaxTokenCountRecordsNoError() : void
    {
        $parser = new MailMimeParser(null, ['maxHeaderTokenCount' => 10]);
        $message = $parser->parse("Subject: word word\r\n\r\nbody\r\n", false);
        $header = $message->getHeader('Subject');
        $this->assertSame('word word', $header->getValue());
        $this->assertEmpty($header->getAllErrors(true));
    }

    private function multipartWithContentTypes(int $count) : string
    {
        return "Content-Type: multipart/mixed; boundary=b\r\n\r\n"
            . \str_repeat("--b\r\nContent-Type: text/plain; charset=utf-8\r\n\r\nx\r\n", $count)
            . "--b--\r\n";
    }

    public function testHeaderTokensBeyondMessageMaxRecordError() : void
    {
        $parser = new MailMimeParser(null, ['maxMessageHeaderTokenCount' => 15]);
        $message = $parser->parse($this->multipartWithContentTypes(6), false);
        $this->assertSame(6, $message->getChildCount());
        $this->assertErrorRecorded($message, 'Message header token limit of 15 reached');
    }

    public function testHeaderTokensBeyondMessageMaxKeepTheirValue() : void
    {
        $parser = new MailMimeParser(null, ['maxMessageHeaderTokenCount' => 15]);
        $message = $parser->parse($this->multipartWithContentTypes(6), false);
        $last = $message->getChild(5);
        // past the limit the whole value is kept as a single unparsed token
        $this->assertSame('text/plain; charset=utf-8', $last->getHeaderValue('Content-Type'));
        $this->assertNull($last->getHeaderParameter('Content-Type', 'charset'));
        $this->assertErrorRecorded($last->getHeader('Content-Type'), 'token limit of 0 reached');
    }

    public function testHeaderTokensUnderMessageMaxRecordNoError() : void
    {
        $parser = new MailMimeParser(null, ['maxMessageHeaderTokenCount' => 100]);
        $message = $parser->parse($this->multipartWithContentTypes(6), false);
        $this->assertSame('utf-8', $message->getChild(5)->getHeaderParameter('Content-Type', 'charset'));
        $this->assertEmpty($message->getAllErrors(true));
    }

    private function multipartWithHeaders(int $parts, int $headersPerPart) : string
    {
        $headers = \str_repeat("X-H: v\r\n", $headersPerPart);
        return "Content-Type: multipart/mixed; boundary=b\r\n\r\n"
            . \str_repeat("--b\r\nContent-Type: text/plain\r\n" . $headers . "\r\nx\r\n", $parts)
            . "--b--\r\n";
    }

    public function testHeadersBeyondMessageMaxRecordError() : void
    {
        $parser = new MailMimeParser(null, ['maxMessageHeaderCount' => 12]);
        $message = $parser->parse($this->multipartWithHeaders(6, 4), false);
        $message->getAllParts();
        $this->assertErrorRecorded($message, 'Message header count limit of 12 reached');
    }

    public function testHeadersUnderMessageMaxRecordNoError() : void
    {
        $parser = new MailMimeParser(null, ['maxMessageHeaderCount' => 100]);
        $message = $parser->parse($this->multipartWithHeaders(6, 4), false);
        $this->assertSame(6, $message->getChildCount());
        $this->assertSame('v', $message->getChild(5)->getHeaderValue('X-H'));
        $this->assertEmpty($message->getAllErrors(true));
    }

    public function testMessageHeaderTokenBudgetIsPerMessage() : void
    {
        $parser = new MailMimeParser(null, ['maxMessageHeaderTokenCount' => 15]);
        $first = $parser->parse($this->multipartWithContentTypes(6), false);
        $this->assertErrorRecorded($first, 'Message header token limit of 15 reached');

        $second = $parser->parse($this->multipartWithContentTypes(1), false);
        $this->assertSame('utf-8', $second->getChild(0)->getHeaderParameter('Content-Type', 'charset'));
        $this->assertEmpty($second->getAllErrors(true));
    }

    public function testManyHeaderParametersAreAllParsed() : void
    {
        $raw = "Content-Type: text/plain; charset=utf-8" . \str_repeat('; a=b', 500) . "\r\n\r\nbody\r\n";
        $message = (new MailMimeParser())->parse($raw, false);
        $this->assertSame('utf-8', $message->getHeaderParameter('Content-Type', 'charset'));
        $this->assertSame('text/plain', $message->getHeaderValue('Content-Type'));
    }
}
