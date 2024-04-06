<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

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
    public function __construct(MbWrapper $charsetConverter, string $value, bool $isLiteral = false)
    {
        parent::__construct($charsetConverter, $value);
        $this->value = \preg_replace('/\r|\n/', '', $this->convertEncoding($value));
        $this->isSpace = (!$isLiteral && $this->value !== null && \preg_match('/^\s+$/', $this->value) === 1);
        $this->canIgnoreSpacesAfter = $this->canIgnoreSpacesAfter = $this->isSpace;
    }
}
