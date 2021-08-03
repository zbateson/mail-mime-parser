<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Reads headers from the input stream if {@see PartBuilder::canHaveHeaders}
 * returns true.  isSupported returns true regardless (which is why it's
 * "optional"), which ensures sub-parsers are called.
 *
 * @author Zaahid Bateson
 */
class HeaderParser
{
    /**
     * Ensures the header isn't empty and contains a colon separator character,
     * then splits it and calls $partBuilder->addHeader.
     *
     * @param string $header
     * @param PartBuilder $partBuilder
     */
    private function addRawHeaderToPart($header, PartHeaderContainer $headerContainer)
    {
        if ($header !== '' && strpos($header, ':') !== false) {
            $a = explode(':', $header, 2);
            $headerContainer->add($a[0], trim($a[1]));
        }
    }

    /**
     * Reads header lines up to an empty line, adding them to the passed
     * $headerContainer.
     *
     * @param PartBuilder $partBuilder the current part to add headers to
     */
    public function parse(PartBuilder $partBuilder, PartHeaderContainer $container)
    {
        $handle = $partBuilder->getMessageResourceHandle();
        $header = '';
        do {
            $line = MessageParser::readLine($handle);
            if ($line === false || $line === '' || $line[0] !== "\t" && $line[0] !== ' ') {
                $this->addRawHeaderToPart($header, $container);
                $header = '';
            } else {
                $line = "\r\n" . $line;
            }
            $header .= rtrim($line, "\r\n");
        } while ($header !== '');
    }
}
