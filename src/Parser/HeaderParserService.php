<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Parser;

use Psr\Log\LogLevel;
use ZBateson\MailMimeParser\Message\HeaderBudget;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Reads headers from an input stream, adding them to a PartHeaderContainer.
 *
 * @author Zaahid Bateson
 */
class HeaderParserService
{
    public function __construct(
        private readonly int $maxHeaderCount = 1000,
        private readonly int $maxHeaderSizeBytes = 1048576
    ) {
    }

    /**
     * Ensures the header isn't empty and contains a colon separator character,
     * then splits it and adds it to the passed PartHeaderContainer.
     *
     * @param int $offset read offset for error reporting
     * @param string $header the header line
     * @param PartHeaderContainer $headerContainer the container
     */
    private function addRawHeaderToPart(int $offset, string $header, PartHeaderContainer $headerContainer) : static
    {
        if ($header !== '') {
            if (\strpos($header, ':') !== false) {
                $a = \explode(':', $header, 2);
                $headerContainer->add($a[0], \trim($a[1]));
            } else {
                $headerContainer->addError(
                    "Invalid header found at offset: $offset",
                    LogLevel::ERROR
                );
            }
        }
        return $this;
    }

    /**
     * Reads header lines up to an empty line, adding them to the passed
     * PartHeaderContainer.
     *
     * @param resource $handle The resource handle to read from.
     * @param PartHeaderContainer $container the container to add headers to.
     */
    public function parse($handle, PartHeaderContainer $container) : static
    {
        $header = '';
        $count = 0;
        $start = \ftell($handle);
        $budget = $container->getBudget();
        // once a limit is reached the rest of the header block is still read
        // past, so it isn't mistaken for the part's content
        $discarding = false;
        do {
            $offset = \ftell($handle);
            $line = MessageParserService::readLine($handle);
            $trimmed = ($line === false) ? '' : \rtrim($line, "\r\n");
            $ended = ($trimmed === '');
            // by position rather than length, since readLine truncates long
            // lines but still consumes them
            $budget?->consumeBytes(\ftell($handle) - $offset);
            if ($ended || ($line[0] !== "\t" && $line[0] !== ' ')) {
                if ($header !== '') {
                    ++$count;
                    $budget?->consumeHeaders(1);
                    $this->addRawHeaderToPart($offset, $header, $container);
                }
                $header = '';
            } else {
                $trimmed = "\r\n" . $trimmed;
            }
            if ($discarding) {
                continue;
            }
            $header .= $trimmed;
            $limitError = $this->getLimitError($count, \ftell($handle) - $start, $budget);
            if ($limitError !== null) {
                $container->addError($limitError, LogLevel::ERROR);
                $discarding = true;
                $header = '';
            }
        } while (!$ended);
        return $this;
    }

    /**
     * Returns an error message if a header limit has been reached, or null.
     */
    private function getLimitError(int $count, int $bytes, ?HeaderBudget $budget) : ?string
    {
        if ($count >= $this->maxHeaderCount || $bytes >= $this->maxHeaderSizeBytes) {
            return 'Header count or total size limit reached while parsing headers';
        }
        if ($budget !== null && $budget->getRemainingHeaders() === 0) {
            return 'Message header count limit of ' . $budget->getMaxHeaderCount()
                . ' reached while parsing headers';
        }
        if ($budget !== null && $budget->getRemainingBytes() === 0) {
            return 'Message header size limit of ' . $budget->getMaxSizeBytes()
                . ' bytes reached while parsing headers';
        }
        return null;
    }
}
