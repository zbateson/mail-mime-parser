<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\CommentPart;

/**
 * Abstract base class representing a mime email's header.
 *
 * The base class sets up the header's consumer for parsing, sets the name of
 * the header, and calls the consumer to parse the header's value.
 *
 * AbstractHeader::getConsumer is an abstract method that must be overridden to
 * return an appropriate Consumer\AbstractConsumer type.
 *
 * @author Zaahid Bateson
 */
abstract class AbstractHeader implements IHeader
{
    /**
     * @var string the name of the header
     */
    protected $name;

    /**
     * @var IHeaderPart[] all parts not including CommentParts.
     */
    protected $parts;

    /**
     * @var IHeaderPart[] the header's parts (as returned from the consumer),
     *      including commentParts
     */
    protected $allParts;

    /**
     * @var string the raw value
     */
    protected $rawValue;

    /**
     * Assigns the header's name and raw value, then calls getConsumer and
     * setParseHeaderValue to extract a parsed value.
     *
     * @param ConsumerService $consumerService For parsing the value.
     * @param string $name Name of the header.
     * @param string $value Value of the header.
     */
    public function __construct(ConsumerService $consumerService, string $name, string $value)
    {
        $this->name = $name;
        $this->rawValue = $value;

        $consumer = $this->getConsumer($consumerService);
        $this->parseHeaderValue($consumer, $this->rawValue);
    }

    /**
     * Returns the header's Consumer
     *
     * @return AbstractConsumer
     */
    abstract protected function getConsumer(ConsumerService $consumerService);

    /**
     * Calls the consumer and assigns the parsed parts to member variables.
     *
     * The default implementation assigns the returned value to $this->allParts
     * and filters out comments from it, assigning the filtered array to
     * $this->parts.
     */
    protected function parseHeaderValue(AbstractConsumer $consumer, string $value) : void
    {
        $this->allParts = $consumer($this->rawValue);
        $this->parts = \array_filter($this->parts, function ($p) {
            return !($p instanceof CommentPart);
        });
    }

    /**
     * @return IHeaderPart[]
     */
    public function getParts() : array
    {
        return $this->parts;
    }

    /**
     * @return IHeaderPart[]
     */
    public function getAllParts() : array
    {
        return $this->allParts;
    }

    public function getValue() : ?string
    {
        if (!empty($this->parts)) {
            return $this->parts[0]->getValue();
        }
        return null;
    }

    public function getRawValue() : string
    {
        return $this->rawValue;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function __toString() : string
    {
        return "{$this->name}: {$this->rawValue}";
    }
}
