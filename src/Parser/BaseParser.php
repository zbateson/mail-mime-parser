<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMessageFactory;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\StreamWrapper;

/**
 * Description of BaseParser
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class BaseParser extends AbstractParser
{
    /**
     * @var ParsedMessageFactory used to create ParsedMessage objects
     */
    protected $parsedMessageFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedMessageFactory $pmf
    ) {
        parent::__construct($pbf);
        $this->parsedMessageFactory = $pmf;
    }

    /**
     * Reads the message from the passed stream and returns a PartBuilder
     * representing it.
     * 
     * @param StreamInterface $stream
     * @return PartBuilder
     */
    public function parseMessage(StreamInterface $stream)
    {
        $partBuilder = $this->partBuilderFactory->newPartBuilder(
            $this->parsedMessageFactory
        );
        $this(StreamWrapper::getResource($stream), $partBuilder);
        return $partBuilder;
    }

    protected function parse($handle, PartBuilder $partBuilder)
    {
        // do nothing
    }

    public function isSupported(PartBuilder $partBuilder)
    {
        return true;
    }
}
