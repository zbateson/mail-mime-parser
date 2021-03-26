<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IMultiPart;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Parser\ParserProxy;

/**
 * Description of ParsedPartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildrenContainer extends PartChildrenContainer
{
    /**
     * @var ParserProxy
     */
    protected $parserProxy;

    /**
     * @var bool
     */
    private $allParsed = false;

    public function setProxyParser(ParserProxy $proxy)
    {
        $this->parserProxy = $proxy;
    }

    public function next()
    {
        $cur = $this->current();
        if ($cur !== null) {
            $cur->hasContent();
            if ($cur instanceof IMultiPart) {
                $cur->getAllParts();
            }
        }
        parent::next();
    }

    public function valid()
    {
        $valid = parent::valid();
        if (!$valid && !$this->allParsed) {
            $this->allParsed = !$this->parserProxy->readNextChild();
            $valid = parent::valid();
        }
        return $valid;
    }
}
