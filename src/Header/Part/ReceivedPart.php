<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;

/**
 * Represents one parameter in a parsed 'Received' header, e.g. the FROM or VIA
 * part.
 *
 * Note that FROM and BY actually get parsed into a sub-class,
 * ReceivedDomainPart which keeps track of other sub-parts that can be parsed
 * from them.
 *
 * @author Zaahid Bateson
 */
class ReceivedPart extends NameValuePart
{
    /**
     * @param HeaderPart[] $children
     */
    public function __construct(
        MbWrapper $charsetConverter,
        HeaderPartFactory $headerPartFactory,
        string $name,
        array $children
    ) {
        parent::__construct($charsetConverter, $headerPartFactory, [], $children);
        $this->name = $name;
    }
}
