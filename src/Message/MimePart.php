<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\ParameterHeader;

/**
 * Implementation of IMimePart.
 *
 * @author Zaahid Bateson
 */
class MimePart extends MultiPart implements IMimePart
{
    /**
     * @var HeaderContainer Contains headers for this part.
     */
    protected $headerContainer;

    public function __construct(
        IMimePart $parent = null,
        PartStreamContainer $streamContainer = null,
        HeaderContainer $headerContainer = null,
        PartChildrenContainer $partChildrenContainer = null
    ) {
        $setStream = false;
        $di = MailMimeParser::getDependencyContainer();
        if ($streamContainer === null || $headerContainer === null || $partChildrenContainer === null) {
            $headerContainer = $di['\ZBateson\MailMimeParser\Message\HeaderContainer'];
            $streamContainer = $di['\ZBateson\MailMimeParser\Message\PartStreamContainer'];
            $partChildrenContainer = $di['\ZBateson\MailMimeParser\Message\PartChildrenContainer'];
            $setStream = true;
        }
        parent::__construct(
            $parent,
            $streamContainer,
            $partChildrenContainer
        );
        if ($setStream) {
            $streamFactory = $di['\ZBateson\MailMimeParser\Stream\StreamFactory'];
            $streamContainer->setStream($streamFactory->newMessagePartStream($this));
        }
        $this->headerContainer = $headerContainer;
    }

    public function getFilename()
    {
        return $this->getHeaderParameter(
            'Content-Disposition',
            'filename',
            $this->getHeaderParameter(
                'Content-Type',
                'name'
            )
        );
    }

    public function isMime()
    {
        return true;
    }

    public function isTextPart()
    {
        return ($this->getCharset() !== null);
    }

    public function getContentType($default = 'text/plain')
    {
        return trim(strtolower($this->getHeaderValue('Content-Type', $default)));
    }

    public function getCharset()
    {
        $charset = $this->getHeaderParameter('Content-Type', 'charset');
        if ($charset === null || strcasecmp($charset, 'binary') === 0) {
            $contentType = $this->getContentType();
            if ($contentType === 'text/plain' || $contentType === 'text/html') {
                return 'ISO-8859-1';
            }
            return null;
        }
        return trim(strtoupper($charset));
    }

    public function getContentDisposition($default = 'inline')
    {
        return strtolower($this->getHeaderValue('Content-Disposition', $default));
    }

    public function getContentTransferEncoding($default = '7bit')
    {
        static $translated = [
            'x-uue' => 'x-uuencode',
            'uue' => 'x-uuencode',
            'uuencode' => 'x-uuencode'
        ];
        $type = strtolower($this->getHeaderValue('Content-Transfer-Encoding', $default));
        if (isset($translated[$type])) {
            return $translated[$type];
        }
        return $type;
    }

    public function getContentId()
    {
        return $this->getHeaderValue('Content-ID');
    }

    public function isSignaturePart()
    {
        if ($this->parent === null || !$this->parent instanceof IMessage) {
            return false;
        }
        return $this->parent->getSignaturePart() === $this;
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
