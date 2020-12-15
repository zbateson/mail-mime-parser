<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use Psr\Http\Message\StreamInterface;

/**
 * Parses a mail mime message into its component parts.  To invoke, call
 * MailMimeParser::parse.
 *
 * @author Zaahid Bateson
 */
class MessageParser
{
    /**
     * @var BaseParser
     */
    protected $baseParser;

    /**
     * Sets up the parser with its dependencies.
     * 
     */
    public function __construct(BaseParser $baseParser) {
        $this->baseParser = $baseParser;
    }
    
    /**
     * Parses the passed stream into a ZBateson\MailMimeParser\Message object
     * and returns it.
     * 
     * @param StreamInterface $stream the stream to parse the message from
     * @return \ZBateson\MailMimeParser\Message
     */
    public function parse(StreamInterface $stream)
    {
        $partBuilder = $this->baseParser->parseMessage($stream);
        return $partBuilder->createMessagePart($stream);
    }
}
