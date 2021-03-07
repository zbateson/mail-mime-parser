<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MailMimeParser\Parser\BaseParser;

/**
 * Responsible for creating specialized IMessagePart instances for parts that
 * were parsed from an email text stream.
 *
 * @author Zaahid Bateson
 */
abstract class ParsedMessagePartFactory
{
    /**
     * @var StreamFactory the StreamFactory instance
     */
    protected $streamFactory;

    /**
     * @var ParsedPartStreamContainerFactory
     */
    protected $parsedPartStreamContainerFactory;

    protected $baseParser;

    public function __construct(
        StreamFactory $sdf,
        ParsedPartStreamContainerFactory $parsedPartStreamContainerFactory,
        BaseParser $baseParser
    ) {
        $this->streamFactory = $sdf;
        $this->parsedPartStreamContainerFactory = $parsedPartStreamContainerFactory;
        $this->baseParser = $baseParser;
    }

    /**
     * Constructs a new IMessagePart object and returns it
     * 
     * @param PartBuilder $partBuilder
     * @return IMessagePart
     */
    public abstract function newInstance(PartBuilder $partBuilder);
}
