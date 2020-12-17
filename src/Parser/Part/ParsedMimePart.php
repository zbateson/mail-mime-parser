<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\MimePartDecoratorTrait;

/**
 * Description of ParsedMimePart
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class ParsedMimePart extends ParsedMessagePart implements IMimePart {

    use MimePartDecoratorTrait {
        MimePartDecoratorTrait::getStream as protected getMessagePartStream;
        MimePartDecoratorTrait::__construct as private mpdt__construct;
    }
}
