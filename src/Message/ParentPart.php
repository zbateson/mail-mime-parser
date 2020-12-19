<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Message\PartFilter;

/**
 * A MessagePart that contains children.
 *
 * @author Zaahid Bateson
 */
abstract class ParentPart extends MessagePart implements IParentPart
{
    /**
     * @var PartFilterFactory factory object responsible for create PartFilters
     */
    protected $partFilterFactory;

    /**
     * @var IMessagePart[] array of child parts
     */
    protected $children = [];

    public function __construct(
        PartStreamContainer $streamContainer,
        PartFilterFactory $partFilterFactory,
        array $children = []
    ) {
        parent::__construct($streamContainer);
        $this->partFilterFactory = $partFilterFactory;
        foreach ($children as $child) {
            $child->parent = $this;
        }
        $this->children = $children;
    }

    /**
     * Returns all parts, including the current object, and all children below
     * it (including children of children, etc...)
     *
     * @return IMessagePart[]
     */
    protected function getAllNonFilteredParts()
    {
        $parts = [ $this ];
        foreach ($this->children as $part) {
            if ($part instanceof MimePart) {
                $parts = array_merge(
                    $parts,
                    $part->getAllNonFilteredParts()
                );
            } else {
                array_push($parts, $part);
            }
        }
        
        return $parts;
    }

    public function getPart($index, PartFilter $filter = null)
    {
        $parts = $this->getAllParts($filter);
        if (!isset($parts[$index])) {
            return null;
        }
        return $parts[$index];
    }

    public function getAllParts(PartFilter $filter = null)
    {
        $parts = $this->getAllNonFilteredParts();
        if (!empty($filter)) {
            return array_values(array_filter(
                $parts,
                [ $filter, 'filter' ]
            ));
        }
        return $parts;
    }

    public function getPartCount(PartFilter $filter = null)
    {
        return count($this->getAllParts($filter));
    }

    public function getChild($index, PartFilter $filter = null)
    {
        $parts = $this->getChildParts($filter);
        if (!isset($parts[$index])) {
            return null;
        }
        return $parts[$index];
    }

    public function getChildParts(PartFilter $filter = null)
    {
        if ($filter !== null) {
            return array_values(array_filter($this->children, [ $filter, 'filter' ]));
        }
        return $this->children;
    }

    public function getChildCount(PartFilter $filter = null)
    {
        return count($this->getChildParts($filter));
    }

    public function getPartByMimeType($mimeType, $index = 0)
    {
        $partFilter = $this->partFilterFactory->newFilterFromContentType($mimeType);
        return $this->getPart($index, $partFilter);
    }

    public function getAllPartsByMimeType($mimeType)
    {
        $partFilter = $this->partFilterFactory->newFilterFromContentType($mimeType);
        return $this->getAllParts($partFilter);
    }

    public function getCountOfPartsByMimeType($mimeType)
    {
        $partFilter = $this->partFilterFactory->newFilterFromContentType($mimeType);
        return $this->getPartCount($partFilter);
    }

    public function addChild(IMessagePart $part, $position = null)
    {
        if ($part !== $this) {
            $part->parent = $this;
            array_splice(
                $this->children,
                ($position === null) ? count($this->children) : $position,
                0,
                [ $part ]
            );
            $this->notify();
        }
    }

    public function removePart(IMessagePart $part)
    {
        $parent = $part->getParent();
        if ($this !== $parent && $parent !== null) {
            return $parent->removePart($part);
        } else {
            $position = array_search($part, $this->children, true);
            if ($position !== false && is_int($position)) {
                array_splice($this->children, $position, 1);
                $this->notify();
                return $position;
            }
        }
        return null;
    }

    public function removeAllParts(PartFilter $filter = null)
    {
        foreach ($this->getAllParts($filter) as $part) {
            if ($part === $this) {
                continue;
            }
            $this->removePart($part);
        }
    }
}
