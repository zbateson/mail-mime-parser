<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

/**
 * Reads headers from the input stream if {@see PartBuilder::canHaveHeaders}
 * returns true.  isSupported returns true regardless (which is why it's
 * "optional"), which ensures sub-parsers are called.
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class OptionalHeaderParser extends AbstractParser
{
    /**
     * Ensures the header isn't empty and contains a colon separator character,
     * then splits it and calls $partBuilder->addHeader.
     *
     * @param string $header
     * @param PartBuilder $partBuilder
     */
    private function addRawHeaderToPart($header, PartBuilder $partBuilder)
    {
        if ($header !== '' && strpos($header, ':') !== false) {
            $a = explode(':', $header, 2);
            $partBuilder->addHeader($a[0], trim($a[1]));
        }
    }

    /**
     * Reads header lines up to an empty line, adding them to the passed
     * $partBuilder.
     *
     * @param resource $handle the resource handle to read from
     * @param PartBuilder $partBuilder the current part to add headers to
     */
    private function readHeaders($handle, PartBuilder $partBuilder)
    {
        $header = '';
        do {
            $line = $this->readLine($handle);
            if (empty($line) || $line[0] !== "\t" && $line[0] !== ' ') {
                $this->addRawHeaderToPart($header, $partBuilder);
                $header = '';
            } else {
                $line = "\r\n" . $line;
            }
            $header .= rtrim($line, "\r\n");
        } while ($header !== '');
    }

    protected function parse($handle, PartBuilder $partBuilder)
    {
        if ($partBuilder->canHaveHeaders()) {
            $this->readHeaders($handle, $partBuilder);
        }
    }

    /**
     * {@inheritedDoc}
     *
     * Always returns true for OptionalHeaderParser so any sub-parsers are
     * executed after.
     *
     * @param PartBuilder $partBuilder
     * @return boolean
     */
    protected function isSupported(PartBuilder $partBuilder)
    {
        return true;
    }
}
