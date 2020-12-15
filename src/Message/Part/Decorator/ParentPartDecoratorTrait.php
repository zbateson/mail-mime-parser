<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part\Decorator;

use ZBateson\MailMimeParser\Message\Part\IMessagePart;
use ZBateson\MailMimeParser\Message\Part\IParentPart;
use ZBateson\MailMimeParser\Message\PartFilter;

/**
 * Description of ParentPartDecoratorTrait
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait ParentPartDecoratorTrait {

    use MessagePartDecoratorTrait;

    /**
     * @var IParentPart The underlying part to wrap.
     */
    protected $part;

    public function __construct(IParentPart $part)
    {
        $this->part = $part;
    }

    public function getPart($index, PartFilter $filter = null)
    {
        return $this->part->getPart($index, $filter);
    }

    public function getAllParts(PartFilter $filter = null)
    {
        return $this->part->getAllParts($filter);
    }

    public function getPartCount(PartFilter $filter = null)
    {
        return $this->part->getPartCount($filter);
    }

    public function addChild(IMessagePart $part, $position = null)
    {
        return $this->part->addChild($part, $position);
    }

    public function getAllPartsByMimeType($mimeType)
    {
        return $this->part->getAllPartsByMimeType($mimeType);
    }

    public function getChild($index, PartFilter $filter = null)
    {
        return $this->part->getChild($index, $filter);
    }

    public function getChildCount(PartFilter $filter = null)
    {
        return $this->part->getChildCount($filter);
    }

    public function getChildParts(PartFilter $filter = null)
    {
        return $this->getChildParts($filter);
    }

    public function getCountOfPartsByMimeType($mimeType)
    {
        return $this->getCountOfPartsByMimeType($mimeType);
    }

    public function getPartByMimeType($mimeType, $index = 0)
    {
        return $this->getPartByMimeType($mimeType, $index);
    }

    public function removeAllParts(PartFilter $filter = null)
    {
        $this->removeAllParts($filter);
    }

    public function removePart(MessagePart $part)
    {
        return $this->removePart($part);
    }
}
