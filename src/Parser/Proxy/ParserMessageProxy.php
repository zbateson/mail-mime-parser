<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Parser\Proxy;

/**
 * A bi-directional parser-to-part proxy for IMessage objects created by
 * MimeParser.
 *
 * @author Zaahid Bateson
 */
class ParserMessageProxy extends ParserMimePartProxy
{
    /**
     * @var int maintains the character length of the last line separator,
     *      typically 2 for CRLF, to keep track of the correct 'end' position
     *      for a part because the CRLF before a boundary is considered part of
     *      the boundary.
     */
    protected int $lastLineEndingLength = 0;

    /**
     * @var int the number of parts created so far while parsing this message,
     *      used to bound the total number of parts a single message may
     *      create.
     */
    protected int $partCount = 0;

    public function getLastLineEndingLength() : int
    {
        return $this->lastLineEndingLength;
    }

    public function setLastLineEndingLength(int $lastLineEndingLength) : static
    {
        $this->lastLineEndingLength = $lastLineEndingLength;
        return $this;
    }

    public function getPartCount() : int
    {
        return $this->partCount;
    }

    public function incrementPartCount() : static
    {
        ++$this->partCount;
        return $this;
    }
}
