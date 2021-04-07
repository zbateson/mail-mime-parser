<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Message\PartFilter;
use Iterator;
use AppendIterator;
use ArrayIterator;
use RecursiveIteratorIterator;

/**
 * A MultiPart mime part.
 *
 * @author Zaahid Bateson
 */
class MultiPart extends MimePart implements IMultiPart
{
    /**
     * @var PartChildrenContainer child part container
     */
    protected $partChildrenContainer;

    public function __construct(
        IMimePart $parent = null,
        PartStreamContainer $streamContainer = null,
        HeaderContainer $headerContainer = null,
        PartChildrenContainer $partChildrenContainer = null
    ) {
        parent::__construct($parent, $streamContainer, $headerContainer);
        if ($partChildrenContainer === null) {
            $di = MailMimeParser::getDependencyContainer();
            $partChildrenContainer = $di['\ZBateson\MailMimeParser\Message\PartChildrenContainer'];
        }
        $this->partChildrenContainer = $partChildrenContainer;
    }

    public function isMultiPart()
    {
        return true;
    }

    private function getAllPartsIterator()
    {
        $iter = new AppendIterator();
        $iter->append(new ArrayIterator([ $this ]));
        $iter->append(new RecursiveIteratorIterator($this->partChildrenContainer, RecursiveIteratorIterator::SELF_FIRST));
        return $iter;
    }

    private function iteratorFindAt(Iterator $iter, $index, $fnFilter = null)
    {
        $pos = 0;
        foreach ($iter as $part) {
            if (($fnFilter === null || $fnFilter($part))) {
                if ($index === $pos) {
                    return $part;
                }
                ++$pos;
            }
        }
    }

    public function getPart($index, $fnFilter = null)
    {
        return $this->iteratorFindAt(
            $this->getAllPartsIterator(),
            $index,
            $fnFilter
        );
    }

    public function getAllParts($fnFilter = null)
    {
        $array = iterator_to_array($this->getAllPartsIterator(), false);
        if ($fnFilter !== null) {
            return array_values(array_filter($array, $fnFilter));
        }
        return $array;
    }

    public function getPartCount($fnFilter = null)
    {
        return count($this->getAllParts($fnFilter));
    }

    public function getChild($index, $fnFilter = null)
    {
        return $this->iteratorFindAt(
            $this->partChildrenContainer,
            $index,
            $fnFilter
        );
    }

    public function getChildIterator()
    {
        return $this->partChildrenContainer;
    }

    public function getChildParts($fnFilter = null)
    {
        $array = iterator_to_array($this->partChildrenContainer, false);
        if ($fnFilter !== null) {
            return array_values(array_filter($array, $fnFilter));
        }
        return $array;
    }

    public function getChildCount($fnFilter = null)
    {
        return count($this->getChildParts($fnFilter));
    }

    public function getPartByMimeType($mimeType, $index = 0)
    {
        return $this->getPart($index, PartFilter::fromContentType($mimeType));
    }

    public function getAllPartsByMimeType($mimeType)
    {
        return $this->getAllParts($partFilter, PartFilter::fromContentType($mimeType));
    }

    public function getCountOfPartsByMimeType($mimeType)
    {
        return $this->getPartCount(PartFilter::fromContentType($mimeType));
    }

    public function addChild(IMessagePart $part, $position = null)
    {
        if ($part !== $this) {
            $part->parent = $this;
            $this->partChildrenContainer->add($part, $position);
            $this->notify();
        }
    }

    public function removePart(IMessagePart $part)
    {
        $parent = $part->getParent();
        if ($this !== $parent && $parent !== null) {
            return $parent->removePart($part);
        } else {
            $position = $this->partChildrenContainer->remove($part);
            if ($position !== null) {
                $this->notify();
            }
            return $position;
        }
    }

    public function removeAllParts($fnFilter = null)
    {
        foreach ($this->getAllParts($fnFilter) as $part) {
            if ($part === $this) {
                continue;
            }
            $this->removePart($part);
        }
    }
}
