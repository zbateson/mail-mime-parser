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
     * @var PartChildrenContainer child part container
     */
    protected $partChildrenContainer;

    /**
     * @var PartFilterFactory factory object responsible for create PartFilters
     */
    protected $partFilterFactory;

    public function __construct(
        PartStreamContainer $streamContainer,
        PartChildrenContainer $partChildrenContainer,
        PartFilterFactory $partFilterFactory,
        array $children = []
    ) {
        parent::__construct($streamContainer);
        $this->partChildrenContainer = $partChildrenContainer;
        $this->partFilterFactory = $partFilterFactory;
        foreach ($children as $child) {
            $child->parent = $this;
        }
        $this->partChildrenContainer->setChildren($children);
        $this->partChildrenContainer->setPart($this);
    }

    public function getPartChildrenContainer()
    {
        return $this->partChildrenContainer;
    }

    public function getPart($index, PartFilter $filter = null)
    {
        return $this->partChildrenContainer->getPart($index, $filter);
    }

    public function getAllParts(PartFilter $filter = null)
    {
        return $this->partChildrenContainer->getAllParts($filter);
    }

    public function getPartCount(PartFilter $filter = null)
    {
        return count($this->getAllParts($filter));
    }

    public function getChild($index, PartFilter $filter = null)
    {
        return $this->partChildrenContainer->getChild($index, $filter);
    }

    public function getChildParts(PartFilter $filter = null)
    {
        return $this->partChildrenContainer->getChildParts($filter);
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
            $this->partChildrenContainer->addChild($part, $position);
            $this->notify();
        }
    }

    public function removePart(IMessagePart $part)
    {
        $parent = $part->getParent();
        if ($this !== $parent && $parent !== null) {
            return $parent->removePart($part);
        } else {
            $position = $this->partChildrenContainer->removePart($part);
            if ($position !== null) {
                $this->notify();
            }
            return $position;
        }
    }

    public function removeAllParts(PartFilter $filter = null)
    {
        $this->partChildrenContainer->removeAllParts($filter);
    }
}
