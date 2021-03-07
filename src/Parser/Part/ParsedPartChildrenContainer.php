<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use ZBateson\MailMimeParser\Message\PartFilter;
use ZBateson\MailMimeParser\Message\IParentPart;

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

    protected $allPartsParsed = false;

    public function setProxyParser(ParserProxy $proxy)
    {
        $this->parserProxy = $proxy;
    }
    
    protected function parseNextPart()
    {
        $this->part->hasContent();
        if (!$this->allPartsParsed) {
            $count = count($this->children);
            if (!empty($this->children)) {
                // read stream and children
                $lastChild = $this->children[count($this->children) - 1];
                $lastChild->hasContent();
                if ($lastChild instanceof IParentPart) {
                    $lastChild->getAllParts();
                }
            }
            $this->allPartsParsed = !$this->parserProxy->readNextChild();
            if (count($this->children) > $count) {
                return $this->children[count($this->children) - 1];
            }
        }
        return null;
    }
    
    public function addParsedChild(IMessagePart $child)
    {
        $this->children[] = $child;
        $child->setParent($this->part);
    }

    protected function getNextPart(&$pos, $index, PartFilter $filter = null)
    {
        if ($filter === null || $filter->filter($this->part)) {
            if ($index === $pos) {
                return $this->part;
            }
            ++$pos;
        }
        foreach ($this->children as $child) {
            $container = ($child instanceof IParentPart) ? $child->getPartChildrenContainer() : null;
            if ($container !== null) {
                $found = $container->getNextPart($pos, $index, $filter);
                if ($found !== null) {
                    return $found;
                }
            } elseif ($filter === null || $filter->filter($child)) {
                if ($index === $pos) {
                    return $child;
                }
                ++$pos;
            }
        }
        while (($child = $this->parseNextPart()) !== null) {
            $container = ($child instanceof IParentPart) ? $child->getPartChildrenContainer() : null;
            if ($container !== null) {
                $found = $container->getNextPart($pos, $index, $filter);
                if ($found !== null) {
                    return $found;
                }
            } elseif ($filter === null || $filter->filter($child)) {
                if ($index === $pos) {
                    return $child;
                }
                ++$pos;
            }
        }
        return null;
    }

    protected function getNextChild(&$pos, $index, PartFilter $filter = null)
    {
        foreach ($this->children as $child) {
            if ($filter === null || $filter->filter($child)) {
                if ($index === $pos) {
                    return $child;
                }
                ++$pos;
            }
        }
        while (!$this->allPartsParsed) {
            $child = $this->parseNextPart();
            if ($child === null) {
                return false;
            }
            if ($filter === null || $filter->filter($child)) {
                if ($index === $pos) {
                    return $child;
                }
                ++$pos;
            }
            $lastChild = $child;
        }

        return null;
    }

    public function getPart($index, PartFilter $filter = null)
    {
        if ($this->allPartsParsed) {
            return parent::getPart($index, $filter);
        }
        $pos = 0;
        $child = $this->getNextPart($pos, $index, $filter);
        return $child;
    }

    public function getAllParts(PartFilter $filter = null)
    {
        while (!$this->allPartsParsed) {
            $this->parseNextPart();
        }
        return parent::getAllParts($filter);
    }

    public function getChild($index, PartFilter $filter = null)
    {
        if ($this->allPartsParsed) {
            return parent::getChild($index, $filter);
        }
        $pos = 0;
        $child = $this->getNextChild($pos, $index, $filter);
        return $child;
    }

    public function getChildParts(PartFilter $filter = null)
    {
        while (!$this->allPartsParsed) {
            $this->parseNextPart();
        }
        return parent::getChildParts($filter);
    }

    public function addChild(IMessagePart $part, $position = null)
    {
        while (!$this->allPartsParsed) {
            $this->parseNextPart();
        }
        return parent::addChild($part, $position);
    }

    public function removePart(IMessagePart $part)
    {
        while (!$this->allPartsParsed) {
            $this->parseNextPart();
        }
        return parent::removePart($part);
    }

    public function removeAllParts(PartFilter $filter = null)
    {
        while (!$this->allPartsParsed) {
            $this->parseNextPart();
        }
        return parent::removeAllParts($filter);
    }

    public function getIterator(PartFilter $filter = null)
    {
        while (!$this->allPartsParsed) {
            $this->parseNextPart();
        }
        return parent::getIterator($filter);
    }
}
