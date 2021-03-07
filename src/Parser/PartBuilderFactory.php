<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMessagePartFactory;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Responsible for creating PartBuilder instances.
 * 
 * The PartBuilder instance must be constructed with a MessagePartFactory
 * instance to construct a MessagePart sub-class after parsing a message into
 * PartBuilder instances.
 *
 * @author Zaahid Bateson
 */
class PartBuilderFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;
    
    /**
     * Initializes dependencies
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }
    
    /**
     * Constructs a new PartBuilder object and returns it
     * 
     * @param ParsedMessagePartFactory $messagePartFactory
     * @param StreamInterface $messageStream
     * @return PartBuilder
     */
    public function newPartBuilder(ParsedMessagePartFactory $messagePartFactory, StreamInterface $messageStream = null)
    {
        return new PartBuilder(
            $messagePartFactory,
            $this->headerFactory->newHeaderContainer(),
            $messageStream
        );
    }
}
