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

/**
 * A parent part containing headers.
 *
 * @author Zaahid Bateson
 */
abstract class ParentHeaderPart extends ParentPart
{
    /**
     * @var HeaderFactory the HeaderFactory object used for created headers
     */
    protected $headerFactory;

    /**
     * @var string[][] array of headers, with keys set to lower-cased,
     *      alphanumeric characters of the header's name, and values set to an
     *      array of 2 elements, the first being the header's original name with
     *      non-alphanumeric characters and original case, and the second set to
     *      the header's value.
     */
    protected $rawHeaders;

    /**
     * @var AbstractHeader[] array of parsed header objects populated on-demand,
     * the key is set to the header's name lower-cased, and with
     * non-alphanumeric characters removed.
     */
    protected $headers;

    /**
     * @param PartStreamFilterManager $partStreamFilterManager
     * @param StreamFactory $streamFactory
     * @param PartFilterFactory $partFilterFactory
     * @param HeaderFactory $headerFactory
     * @param PartBuilder $partBuilder
     * @param StreamInterface $stream
     * @param StreamInterface $contentStream
     */
    public function __construct(
        PartStreamFilterManager $partStreamFilterManager,
        StreamFactory $streamFactory,
        PartFilterFactory $partFilterFactory,
        HeaderFactory $headerFactory,
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
        $this->headerFactory = $headerFactory;
        $this->headers['contenttype'] = $partBuilder->getContentType();
        $this->rawHeaders = $partBuilder->getRawHeaders();
    }

    /**
     * Returns the string in lower-case, and with non-alphanumeric characters
     * stripped out.
     *
     * @param string $header
     * @return string
     */
    private function getNormalizedHeaderName($header)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($header));
    }

    /**
     * Returns the AbstractHeader object for the header with the given $name
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @return AbstractHeader
     */
    public function getHeader($name)
    {
        $nameKey = $this->getNormalizedHeaderName($name);
        if (isset($this->rawHeaders[$nameKey])) {
            if (!isset($this->headers[$nameKey])) {
                $this->headers[$nameKey] = $this->headerFactory->newInstance(
                    $this->rawHeaders[$nameKey][0],
                    $this->rawHeaders[$nameKey][1]
                );
            }
            return $this->headers[$nameKey];
        }
        return null;
    }

    /**
     * Returns an array of all headers for the mime part with the first element
     * holding the name, and the second its value.
     *
     * @return string[][]
     */
    public function getRawHeaders()
    {
        return array_values($this->rawHeaders);
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
    public function setRawHeader($name, $value)
    {
        $normalized = $this->getNormalizedHeaderName($name);
        $header = $this->headerFactory->newInstance($name, $value);
        $this->headers[$normalized] = $header;
        $this->rawHeaders[$normalized] = [
            $header->getName(),
            $header->getRawValue()
        ];
        $this->onChange();
    }

    /**
     * Removes the header with the given name
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        $normalized = $this->getNormalizedHeaderName($name);
        unset($this->headers[$normalized], $this->rawHeaders[$normalized]);
        $this->onChange();
    }
}
