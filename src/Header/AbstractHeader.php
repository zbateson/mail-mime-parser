<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\ErrorBag;
use ZBateson\MailMimeParser\Header\Consumer\IConsumerService;
use ZBateson\MailMimeParser\Header\Part\CommentPart;
use Psr\Log\LogLevel;

/**
 * Abstract base class representing a mime email's header.
 *
 * The base class sets up the header's consumer for parsing, sets the name of
 * the header, and calls the consumer to parse the header's value.
 *
 * @author Zaahid Bateson
 */
abstract class AbstractHeader extends ErrorBag implements IHeader
{
    /**
     * @var string the name of the header
     */
    protected $name = '';

    /**
     * @var IHeaderPart[] all parts not including CommentParts.
     */
    protected $parts = [];

    /**
     * @var IHeaderPart[] the header's parts (as returned from the consumer),
     *      including commentParts
     */
    protected $allParts = [];

    /**
     * @var string[] array of comments, initialized on demand in getComments()
     */
    private $comments;

    /**
     * @var string the raw value
     */
    protected $rawValue = '';

    /**
     * Assigns the header's name and raw value, then calls parseHeaderValue to
     * extract a parsed value.
     *
     * @param IConsumerService $consumerService For parsing the value.
     * @param string $name Name of the header.
     * @param string $value Value of the header.
     */
    public function __construct(
        IConsumerService $consumerService,
        string $name,
        string $value
    ) {
        parent::__construct();
        $this->name = $name;
        $this->rawValue = $value;
        $this->parseHeaderValue($consumerService, $value);
    }

    /**
     * Filters $this->allParts into the parts required by $this->parts
     * and assignes it.
     *
     * The AbstractHeader::filterAndAssignToParts method filters out CommentParts.
     */
    protected function filterAndAssignToParts() : void
    {
        $this->parts = \array_values(\array_filter($this->allParts, function ($p) {
            return !($p instanceof CommentPart);
        }));
    }

    /**
     * Calls the consumer and assigns the parsed parts to member variables.
     *
     * The default implementation assigns the returned value to $this->allParts
     * and filters out comments from it, assigning the filtered array to
     * $this->parts by calling filterAndAssignToParts.
     */
    protected function parseHeaderValue(IConsumerService $consumer, string $value) : void
    {
        $this->allParts = $consumer($value);
        $this->filterAndAssignToParts();
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

    /**
     * @return string[]
     */
    public function getComments() : array
    {
        if ($this->comments === null) {
            $this->comments = \array_values(\array_map(
                function ($p) { return $p->getComment(); },
                \array_filter(
                    $this->allParts,
                    function ($p) { return ($p instanceof CommentPart); }
                )
            ));
        }
        return $this->comments;
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

    public function getErrorLoggingContextName(): string
    {
        return 'Header::' . $this->getName();
    }

    protected function getErrorBagChildren() : array
    {
        return $this->getAllParts();
    }

    protected function validate() : void
    {
        if (strlen(trim($this->name)) === 0) {
            $this->addError('Header doesn\'t have a name', LogLevel::ERROR);
        }
        if (strlen(trim($this->rawValue)) === 0) {
            $this->addError('Header doesn\'t have a value', LogLevel::NOTICE);
        }
    }
}
