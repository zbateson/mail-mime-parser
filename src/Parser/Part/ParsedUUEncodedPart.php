<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IUUEncodedPart;
use ZBateson\MailMimeParser\Message\UUEncodedPartDecoratorTrait;

/**
 * Description of ParsedUUEncodedPart
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class ParsedUUEncodedPart extends ParsedMessagePart implements IUUEncodedPart {

    use UUEncodedPartDecoratorTrait {
        UUEncodedPartDecoratorTrait::getStream as protected getMessagePartStream;
        UUEncodedPartDecoratorTrait::__construct as private mpdt__construct;
    }
}
