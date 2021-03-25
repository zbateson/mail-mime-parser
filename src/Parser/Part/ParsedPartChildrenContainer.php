<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\PartChildContained;
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
     * @var ChildContainerIterator
     */
    protected $iterator;

    /**
     *
     */
    public function init(IMimePart $part)
    {
        $this->contained = new ParsedPartChildContained($part, $this);
    }

    public function setProxyParser(ParserProxy $proxy)
    {
        $this->parserProxy = $proxy;
        $this->iterator = new ChildContainerIterator($this->parserProxy, $this->children);
    }

    public function addParsedChild(PartChildContained $child)
    {
        $this->children[] = $child;
    }

    public function ensurePartParsed()
    {
        $this->contained->getPart()->hasContent();
        foreach ($this->iterator as $part) {
            // do nothing
        }
    }

    private function findMatch(PartChildContained $contained, $recurse = false, &$pos, $index, $fnFilter = null)
    {
        // passed 'contained' may be $this->contained, and so $contained->getContainer()
        // needs to be compared to $this
        if ($recurse && $contained->getContainer() !== null && $contained->getContainer() !== $this) {
            $found = $contained->getContainer()->getNextPart($pos, $index, $fnFilter);
            if ($found !== null) {
                return $found;
            }
        } elseif ($fnFilter === null || $fnFilter($contained->getPart())) {
            if ($index === $pos) {
                return $contained->getPart();
            }
            ++$pos;
        }
        return null;
    }

    protected function getNextPart(&$pos, $index, $fnFilter = null)
    {
        $this->iterator->rewind();
        $iter = new \AppendIterator();
        $iter->append(new \ArrayIterator([ $this->contained ]));
        $iter->append($this->iterator);
        foreach ($iter as $child) {
            $matched = $this->findMatch($child, true, $pos, $index, $fnFilter);
            if ($matched !== null) {
                return $matched;
            }
        }
        return null;
    }

    protected function getNextChild(&$pos, $index, $fnFilter = null)
    {
        $this->iterator->rewind();
        foreach ($this->iterator as $child) {
            $matched = $this->findMatch($child, false, $pos, $index, $fnFilter);
            if ($matched !== null) {
                return $matched;
            }
        }
        return null;
    }

    public function getPart($index, $fnFilter = null)
    {
        if ($this->iterator->isAllPartsParsed()) {
            return parent::getPart($index, $fnFilter);
        }
        $pos = 0;
        return $this->getNextPart($pos, $index, $fnFilter);
    }

    public function getAllParts($fnFilter = null)
    {
        $this->ensurePartParsed();
        return parent::getAllParts($fnFilter);
    }

    public function getChild($index, $fnFilter = null)
    {
        if ($this->iterator->isAllPartsParsed()) {
            return parent::getChild($index, $fnFilter);
        }
        $pos = 0;
        return $this->getNextChild($pos, $index, $fnFilter);
    }

    public function getChildParts($fnFilter = null)
    {
        $this->ensurePartParsed();
        return parent::getChildParts($fnFilter);
    }

    public function addChild(PartChildContained $contained, $position = null)
    {
        $this->ensurePartParsed();
        return parent::addChild($contained, $position);
    }

    public function removePart(IMessagePart $part)
    {
        $this->ensurePartParsed();
        return parent::removePart($part);
    }

    public function removeAllParts($fnFilter = null)
    {
        $this->ensurePartParsed();
        return parent::removeAllParts($fnFilter);
    }
}
