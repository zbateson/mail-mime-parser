<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Message\MimePartDecoratorTrait;

/**
 * Description of ProxyMimePart
 *
 * @author Zaahid Bateson
 */
class ProxyMimePart extends MimePart
{
    use MimePartDecoratorTrait {
        MimePartDecoratorTrait::__construct as private traitConstructor;
    }

    protected $partBuilder;

    protected $parser;

    public function __construct(
        MimePart $part,
        PartBuilder $partBuilder
    ) {
        $this->traitConstructor($part);
        $this->parser = $parser;
        $this->partBuilder = $partBuilder;
    }
}
