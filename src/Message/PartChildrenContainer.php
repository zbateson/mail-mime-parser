<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Description of PartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class PartChildrenContainer
{
    /**
     * @var PartChildContained the container's part.
     */
    protected $contained = null;

    /**
     * @var PartChildContained[] contained child parts.
     */
    protected $children = [];

    /**
     * 
     */
    public function init(IMimePart $part)
    {
        $this->contained = new PartChildContained($part, $this);
    }

    public function getPartChildContained()
    {
        return $this->contained;
    }

    /**
     * Returns all parts, including the current object, and all children below
     * it (including children of children, etc...)
     *
     * @return IMessagePart[]
     */
    protected function getAllNonFilteredParts()
    {
        $parts = [ $this->contained->getPart() ];
        foreach ($this->children as $contained) {
            if ($contained->getContainer() !== null) {
                $parts = array_merge(
                    $parts,
                    $contained->getContainer()->getAllNonFilteredParts()
                );
            } else {
                array_push($parts, $contained->getPart());
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
        $mapped = array_map(
            function ($contained) { return $contained->getPart(); },
            $this->children
        );
        if ($fnFilter !== null) {
            return array_values(array_filter($mapped, $fnFilter));
        }
        return $mapped;
    }

    public function addChild(PartChildContained $contained, $position = null)
    {
        array_splice(
            $this->children,
            ($position === null) ? count($this->children) : $position,
            0,
            [ $contained ]
        );
    }

    public function removePart(IMessagePart $part)
    {
        $parent = $part->getParent();
        $position = false;
        foreach ($this->children as $key => $child) {
            if ($child->getPart() === $part) {
                array_splice($this->children, $key, 1);
                return $key;
            }
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
}
