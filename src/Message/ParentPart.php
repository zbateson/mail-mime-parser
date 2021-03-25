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
abstract class ParentPart extends MessagePart
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
        IMessagePart $parent = null
    ) {
        parent::__construct($streamContainer, $parent);
        $this->partChildrenContainer = $partChildrenContainer;
        $this->partFilterFactory = $partFilterFactory;
        $this->partChildrenContainer->init($this);
    }

    public function getPartChildrenContainer()
    {
        return $this->partChildrenContainer;
    }

    public function getPart($index, $fnFilter = null)
    {
        return $this->partChildrenContainer->getPart($index, $fnFilter);
    }

    public function getAllParts($fnFilter = null)
    {
        return $this->partChildrenContainer->getAllParts($fnFilter);
    }

    public function getPartCount($fnFilter = null)
    {
        return count($this->getAllParts($fnFilter));
    }

    public function getChild($index, $fnFilter = null)
    {
        return $this->partChildrenContainer->getChild($index, $fnFilter);
    }

    public function getChildParts($fnFilter = null)
    {
        return $this->partChildrenContainer->getChildParts($fnFilter);
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
            $this->partChildrenContainer->addChild(
                new PartChildContained($part, ($part instanceof ParentPart) ? $part->partChildrenContainer : null),
                $position
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
            $position = $this->partChildrenContainer->removePart($part);
            if ($position !== null) {
                $this->notify();
            }
            return $position;
        }
    }

    public function removeAllParts($fnFilter = null)
    {
        $this->partChildrenContainer->removeAllParts($fnFilter);
    }
}
