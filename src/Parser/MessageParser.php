<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMessageFactory;
use Psr\Http\Message\StreamInterface;

/**
 * Parses a mail mime message into its component parts.  To invoke, call
 * {@see MailMimeParser::parse()}.
 *
 * @author Zaahid Bateson
 */
class MessageParser
{
    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var ParserMessageFactory
     */
    protected $parserMessageFactory;

    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var HeaderParser
     */
    protected $headerParser;

    public function __construct(
        PartBuilderFactory $pbf,
        PartHeaderContainerFactory $phcf,
        ParserMessageFactory $pmf,
        HeaderParser $hp
    ) {
        $this->partBuilderFactory = $pbf;
        $this->partHeaderContainerFactory = $phcf;
        $this->parserMessageFactory = $pmf;
        $this->headerParser = $hp;
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
     * @return string|bool the read line or false on EOF or on error.
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
        $partBuilder = $this->partBuilderFactory->newPartBuilder($stream);
        $headerContainer = $this->partHeaderContainerFactory->newInstance();
        $this->headerParser->parse(
            $partBuilder->getMessageResourceHandle(),
            $headerContainer
        );
        $proxy = $this->parserMessageFactory->newInstance(
            $partBuilder,
            $headerContainer
        );
        return $proxy->getPart();
    }
}
