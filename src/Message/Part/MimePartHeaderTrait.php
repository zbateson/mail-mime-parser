<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\ParameterHeader;

/**
 * Header related methods attached to a mime part.
 *
 * @author Zaahid Bateson
 */
trait MimePartHeaderTrait
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
     * @var \ZBateson\MailMimeParser\Header\AbstractHeader[] array of parsed
     * header objects populated on-demand, the key is set to the header's name
     * lower-cased, and with non-alphanumeric characters removed.
     */
    protected $headers;

    /**
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory, PartBuilder $partBuilder) {
        $this->headerFactory = $headerFactory;
        $this->headers['contenttype'] = $partBuilder->getContentType();
        $this->rawHeaders = $partBuilder->getRawHeaders();
    }

    /**
     * Returns the AbstractHeader object for the header with the given $name
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     */
    public function getHeader($name)
    {
        $nameKey = preg_replace('/[^a-z0-9]/', '', strtolower($name));
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
}
