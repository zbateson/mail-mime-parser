<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Header\Part\HeaderPart;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Holds a string value token that will require additional processing by a
 * consumer prior to returning to a client.
 *
 * A Token is meant to hold a value for further processing -- for instance when
 * consuming an address list header (like From or To) -- before it's known what
 * type of IHeaderPart it is (could be an email address, could be a name, or
 * could be a group.)
 *
 * @author Zaahid Bateson
 */
class Token extends HeaderPart
{
    public function __construct(
        LoggerInterface $logger,
        MbWrapper $charsetConverter,
        string $value,
        bool $isLiteral = false,
        bool $preserveSpaces = false
    ) {
        parent::__construct($logger, $charsetConverter, $value);
        if (!$isLiteral) {
            $this->value = \preg_replace('/\r|\n/', '', $value);
            if (!$preserveSpaces) {
                $this->value = \preg_replace('/^\s+$/m', ' ', $this->value);
            }
        }
        $this->isSpace = ($this->value === '' || (!$isLiteral && \preg_match('/^\s*$/m', $this->value) === 1));
        $this->canIgnoreSpacesAfter = $this->canIgnoreSpacesAfter = $this->isSpace;
    }

    /**
     * Returns the part's representative value after any necessary processing
     * has been performed.  For the raw value, call getRawValue().
     */
    public function getValue() : string
    {
        return $this->convertEncoding($this->value);
    }
}
