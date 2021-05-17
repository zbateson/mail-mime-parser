<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Parser\PartBuilder;

/**
 * Description of ParsedPartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class ParsedPartChildrenContainer extends PartChildrenContainer
{
    /**
     * @var PartBuilder
     */
    protected $partBuilder;

    /**
     * @var bool
     */
    private $allParsed = false;

    public function __construct(PartBuilder $builder)
    {
        parent::__construct([]);
        $this->partBuilder = $builder;
    }

    public function offsetExists($offset)
    {
        $exists = parent::offsetExists($offset);
        while (!$exists && !$this->allParsed) {
            $this->allParsed = !$this->partBuilder->parseNextChild();
            $exists = parent::offsetExists($offset);
        }
        return $exists;
    }
}
