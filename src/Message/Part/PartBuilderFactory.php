<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;

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
     * @var string the PartStream protocol used to create part and content
     *      filenames for fopen
     */
    private $streamWrapperProtocol = null;
    
    /**
     * Creates a MimePartFactory instance with its dependencies.
     * 
     * @param HeaderFactory $headerFactory
     * @param string $streamWrapperProtocol
     */
    public function __construct(HeaderFactory $headerFactory, $streamWrapperProtocol)
    {
        $this->headerFactory = $headerFactory;
        $this->streamWrapperProtocol = $streamWrapperProtocol;
    }
    
    /**
     * Constructs a new PartBuilder object and returns it
     * 
     * @param \ZBateson\MailMimeParser\Message\Part\MessagePartFactory
     *        $messagePartFactory 
     * @return \ZBateson\MailMimeParser\Message\Part\PartBuilder
     */
    public function newPartBuilder(MessagePartFactory $messagePartFactory)
    {
        return new PartBuilder(
            $this->headerFactory,
            $messagePartFactory,
            $this->streamWrapperProtocol
        );
    }
}
