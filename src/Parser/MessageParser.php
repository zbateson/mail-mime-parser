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
 * Parses a mail mime message into its component parts.  To invoke, call
 * {@see MailMimeParser::parse()}.
 *
 * @author Zaahid Bateson
 */
class MessageParser
{
    /**
     * @var ParsedMessageFactory
     */
    protected $parsedMessageFactory;

    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var AbstractParser
     */
    protected $baseParser;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedMessageFactory $pmf,
        BaseParser $baseParser,
        OptionalHeaderParser $headerParser,
        MimeContentParser $mimeParser,
        MultipartChildrenParser $multipartParser,
        NonMimeContentParser $nonMimeParser
    ) {
        $this->parsedMessageFactory = $pmf;
        $this->partBuilderFactory = $pbf;
        $baseParser->addSubParser($headerParser);
        $headerParser->addSubParser($mimeParser);
        $headerParser->addSubParser($nonMimeParser);
        $mimeParser->addSubParser($multipartParser);
        $this->baseParser = $baseParser;
    }
    
    /**
     * Parses the passed stream into a {@see ZBateson\MailMimeParser\IMessage}
     * object and returns it.
     * 
     * @param StreamInterface $stream the stream to parse the message from
     * @return \ZBateson\MailMimeParser\IMessage
     */
    public function parse(StreamInterface $stream)
    {
        $partBuilder = $this->partBuilderFactory->newPartBuilder(
            $this->parsedMessageFactory
        );
        $this->baseParser->__invoke(StreamWrapper::getResource($stream), $partBuilder);
        return $partBuilder->createMessagePart($stream);
    }
}
