<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use IteratorAggregate;

/**
 * Description of PartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class PartChildrenContainer implements IteratorAggregate
{
    /**
     * @var IMessagePart the container's part
     */
    protected $part = null;

    /**
     * @var IMessagePart[] array of child parts
     */
    protected $children = [];

    /**
     * 
     * @param IMessagePart[] $children
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    public function setPart(IMessagePart $part)
    {
        $this->part = $part;
    }

    /**
     * Returns all parts, including the current object, and all children below
     * it (including children of children, etc...)
     *
     * @return IMessagePart[]
     */
    protected function getAllNonFilteredParts()
    {
        $parts = [ $this->part ];
        foreach ($this->children as $part) {
            if ($part instanceof IMimePart) {
                $parts = array_merge(
                    $parts,
                    $part->getAllParts()
                );
            } else {
                array_push($parts, $part);
            }
        }

        return $parts;
    }

    public function getPart($index, $fnFilter = null)
    {
        $parts = $this->getAllParts($fnFilter);
        if (!isset($parts[$index])) {
            return null;
        }
        return $parts[$index];
    }

    public function getAllParts($fnFilter = null)
    {
        $parts = $this->getAllNonFilteredParts();
        if (!empty($fnFilter)) {
            return array_values(array_filter($parts, $fnFilter));
        }
        return $parts;
    }

    public function getChild($index, $fnFilter = null)
    {
        $parts = $this->getChildParts($fnFilter);
        if (!isset($parts[$index])) {
            return null;
        }
        return $parts[$index];
    }

    public function getChildParts($fnFilter = null)
    {
        if ($fnFilter !== null) {
            return array_values(array_filter($this->children, $fnFilter));
        }
        return $this->children;
    }

    public function addChild(IMessagePart $part, $position = null)
    {
        array_splice(
            $this->children,
            ($position === null) ? count($this->children) : $position,
            0,
            [ $part ]
        );
    }

    public function removePart(IMessagePart $part)
    {
        $parent = $part->getParent();
        $position = array_search($part, $this->children, true);
        if ($position !== false && is_int($position)) {
            array_splice($this->children, $position, 1);
            return $position;
        }
        return null;
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

    public function getIterator()
    {
        return new ArrayIterator($this->children);
    }
}
