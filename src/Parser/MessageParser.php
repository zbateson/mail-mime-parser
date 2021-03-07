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
     * @var BaseParser
     */
    protected $baseParser;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedMessageFactory $pmf,
        BaseParser $baseParser,
        MimeContentParser $mimeParser,
        MultipartChildrenParser $multipartParser,
        NonMimeParser $nonMimeParser
    ) {
        $this->parsedMessageFactory = $pmf;
        $this->partBuilderFactory = $pbf;
        $baseParser->addContentParser($mimeParser);
        $baseParser->addContentParser($nonMimeParser);
        $baseParser->addChildParser($multipartParser);
        $baseParser->addChildParser($nonMimeParser);
        $this->baseParser = $baseParser;
    }


    /**
     * Convenience method to read a line of up to 4096 characters from the
     * passed resource handle.
     *
     * If the line is larger than 4096 characters, the remaining characters in
     * the line are read and discarded, and only the first 4096 characters are
     * returned.
     *
     * @param resource $handle
     * @return string
     */
    public static function readLine($handle)
    {
        $size = 4096;
        $ret = $line = fgets($handle, $size);
        while (strlen($line) === $size - 1 && substr($line, -1) !== "\n") {
            $line = fgets($handle, $size);
        }
        return $ret;
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
            $this->parsedMessageFactory,
            $stream
        );
        $this->baseParser->parseHeaders($partBuilder);
        return $partBuilder->createMessagePart();
    }
}
