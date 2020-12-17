<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MessageDecoratorTrait;

/**
 * Description of ParsedMessage
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class ParsedMessage extends ParsedMimePart implements IMessage {

    use MessageDecoratorTrait {
        MessageDecoratorTrait::getStream as protected getMessagePartStream;
        MessageDecoratorTrait::__construct as private mpdt__construct;
    }
}
