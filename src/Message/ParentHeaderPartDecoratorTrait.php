<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\IParentHeaderPart;

/**
 * Ferries calls to an IParentHeaderPart.
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait ParentHeaderPartDecoratorTrait
{
    use ParentPartDecoratorTrait;

    /**
     * @var IParentHeaderPart The underlying part to wrap.
     */
    protected $part;

    public function __construct(IParentHeaderPart $part)
    {
        $this->part = $part;
    }

    public function addRawHeader($name, $value)
    {
        $this->part->addRawHeader($name, $value);
    }

    public function getAllHeaders()
    {
        return $this->part->getAllHeaders();
    }

    public function getAllHeadersByName($name)
    {
        return $this->part->getAllHeadersByName($name);
    }

    public function getHeader($name, $offset = 0)
    {
        return $this->part->getHeader($name, $offset);
    }

    public function getHeaderParameter($header, $param, $defaultValue = null)
    {
        return $this->part->getHeaderParameter($header, $param, $defaultValue);
    }

    public function getHeaderValue($name, $defaultValue = null)
    {
        return $this->part->getHeaderValue($name, $defaultValue);
    }

    public function getRawHeaderIterator()
    {
        return $this->part->getRawHeaderIterator();
    }

    public function getRawHeaders()
    {
        return $this->part->getRawHeaders();
    }

    public function removeHeader($name)
    {
        $this->part->removeHeader($name);
    }

    public function removeSingleHeader($name, $offset = 0)
    {
        $this->part->removeSingleHeader($name, $offset);
    }

    public function setRawHeader($name, $value, $offset = 0)
    {
        $this->part->setRawHeader($name, $value, $offset);
    }
}
