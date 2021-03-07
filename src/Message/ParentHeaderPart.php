<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Message\Factory\PartFilterFactory;
use ZBateson\MailMimeParser\Header\HeaderContainer;

/**
 * A parent part containing headers.
 *
 * @author Zaahid Bateson
 */
abstract class ParentHeaderPart extends ParentPart implements IParentHeaderPart
{
    /**
     * @var HeaderContainer Contains headers for this part.
     */
    protected $headerContainer;

    public function __construct(
        PartStreamContainer $streamContainer,
        HeaderContainer $headerContainer,
        PartChildrenContainer $partChildrenContainer,
        PartFilterFactory $partFilterFactory,
        array $children
    ) {
        parent::__construct(
            $streamContainer,
            $partChildrenContainer,
            $partFilterFactory,
            $children
        );
        $this->headerContainer = $headerContainer;
    }

    public function getHeader($name, $offset = 0)
    {
        return $this->headerContainer->get($name, $offset);
    }

    public function getAllHeaders()
    {
        return $this->headerContainer->getHeaderObjects();
    }

    public function getAllHeadersByName($name)
    {
        return $this->headerContainer->getAll($name);
    }

    public function getRawHeaders()
    {
        return $this->headerContainer->getHeaders();
    }

    public function getRawHeaderIterator()
    {
        return $this->headerContainer->getIterator();
    }

    public function getHeaderValue($name, $defaultValue = null)
    {
        $header = $this->getHeader($name);
        if ($header !== null) {
            return $header->getValue();
        }
        return $defaultValue;
    }

    public function getHeaderParameter($header, $param, $defaultValue = null)
    {
        $obj = $this->getHeader($header);
        if ($obj && $obj instanceof ParameterHeader) {
            return $obj->getValueFor($param, $defaultValue);
        }
        return $defaultValue;
    }

    public function setRawHeader($name, $value, $offset = 0)
    {
        $this->headerContainer->set($name, $value, $offset);
        $this->notify();
    }

    public function addRawHeader($name, $value)
    {
        $this->headerContainer->add($name, $value);
        $this->notify();
    }

    public function removeHeader($name)
    {
        $this->headerContainer->removeAll($name);
        $this->notify();
    }

    public function removeSingleHeader($name, $offset = 0)
    {
        $this->headerContainer->remove($name, $offset);
        $this->notify();
    }
}
