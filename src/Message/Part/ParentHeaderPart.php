<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Message\PartFilterFactory;
use ZBateson\MailMimeParser\Header\HeaderContainer;

/**
 * A parent part containing headers.
 *
 * @author Zaahid Bateson
 */
abstract class ParentHeaderPart extends ParentPart
{
    /**
     * @var HeaderContainer
     */
    protected $headerContainer;

    /**
     * @param PartStreamFilterManager $partStreamFilterManager
     * @param StreamFactory $streamFactory
     * @param PartFilterFactory $partFilterFactory
     * @param PartBuilder $partBuilder
     * @param StreamInterface $stream
     * @param StreamInterface $contentStream
     */
    public function __construct(
        PartStreamFilterManager $partStreamFilterManager,
        StreamFactory $streamFactory,
        PartFilterFactory $partFilterFactory,
        PartBuilder $partBuilder,
        StreamInterface $stream = null,
        StreamInterface $contentStream = null
    ) {
        parent::__construct(
            $partStreamFilterManager,
            $streamFactory,
            $partFilterFactory,
            $partBuilder,
            $stream,
            $contentStream
        );
        $this->headerContainer = $partBuilder->getHeaderContainer();
    }

    /**
     * Returns the AbstractHeader object for the header with the given $name
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     */
    public function getHeader($name, $offset = 0)
    {
        return $this->headerContainer->get($name, $offset);
    }

    /**
     * 
     * @param string $name
     */
    public function getAllHeadersByName($name)
    {
        return $this->headerContainer->getAll($name);
    }

    /**
     * Returns an array of all headers for the mime part with the first element
     * holding the name, and the second its value.
     *
     * @return string[][]
     */
    public function getRawHeaders()
    {
        return $this->headerContainer->getHeaders();
    }

    /**
     * 
     *
     * @return \Iterator
     */
    public function getRawHeaderIterator()
    {
        return $this->headerContainer->getIterator();
    }

    /**
     * Returns the string value for the header with the given $name.
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @param string $defaultValue
     * @return string
     */
    public function getHeaderValue($name, $defaultValue = null)
    {
        $header = $this->getHeader($name);
        if ($header !== null) {
            return $header->getValue();
        }
        return $defaultValue;
    }

    /**
     * Returns a parameter of the header $header, given the parameter named
     * $param.
     *
     * Only headers of type
     * \ZBateson\MailMimeParser\Header\ParameterHeader have parameters.
     * Content-Type and Content-Disposition are examples of headers with
     * parameters. "Charset" is a common parameter of Content-Type.
     *
     * @param string $header
     * @param string $param
     * @param string $defaultValue
     * @return string
     */
    public function getHeaderParameter($header, $param, $defaultValue = null)
    {
        $obj = $this->getHeader($header);
        if ($obj && $obj instanceof ParameterHeader) {
            return $obj->getValueFor($param, $defaultValue);
        }
        return $defaultValue;
    }

    /**
     * Adds a header with the given $name and $value.
     *
     * Creates a new \ZBateson\MailMimeParser\Header\AbstractHeader object and
     * registers it as a header.
     *
     * @param string $name
     * @param string $value
     */
    public function setRawHeader($name, $value, $offset = 0)
    {
        $this->headerContainer->set($name, $value, $offset);
        $this->onChange();
    }

    /**
     * Adds a header with the given $name and $value.
     *
     * Creates a new \ZBateson\MailMimeParser\Header\AbstractHeader object and
     * registers it as a header.
     *
     * @param string $name
     * @param string $value
     */
    public function addRawHeader($name, $value)
    {
        $this->headerContainer->add($name, $value);
        $this->onChange();
    }

    /**
     * Removes the header with the given name
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        $this->headerContainer->removeAll($name);
        $this->onChange();
    }

    /**
     * Removes the header with the given name
     *
     * @param string $name
     */
    public function removeSingleHeader($name, $offset = 0)
    {
        $this->headerContainer->remove($name, $offset);
        $this->onChange();
    }
}
