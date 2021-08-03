<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartProxy;

/**
 * Description of ParsedPartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildrenContainer extends PartChildrenContainer
{
    /**
     * @var ParserMimePartProxy
     */
    protected $parserProxy;

    /**
     * @var bool
     */
    private $allParsed = false;

    public function __construct(ParserMimePartProxy $parserProxy)
    {
        parent::__construct([]);
        $this->parserProxy = $parserProxy;
    }

    public function offsetExists($offset)
    {
        $exists = parent::offsetExists($offset);
        while (!$exists && !$this->allParsed) {
            $child = $this->parserProxy->parseNextChild();
            if ($child === null) {
                $this->allParsed = true;
            }
            $exists = parent::offsetExists($offset);
        }
        return $exists;
    }
}
