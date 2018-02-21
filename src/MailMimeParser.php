<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;

/**
 * Parses a MIME message into a \ZBateson\MailMimeParser\Message object.
 *
 * To invoke, call parse on a MailMimeParser object.
 * 
 * $handle = fopen('path/to/file.txt');
 * $parser = new MailMimeParser();
 * $parser->parse($handle);
 * fclose($handle);
 * 
 * @author Zaahid Bateson
 */
class MailMimeParser
{
    /**
     * @var string defines the default charset used by MessagePart.
     */
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * @var \ZBateson\MailMimeParser\SimpleDi dependency injection container
     */
    protected $di;
    
    /**
     * Sets up the parser.
     */
    public function __construct()
    {
        $this->di = SimpleDi::singleton();
    }
    
    /**
     * Sets the default charset used by MMP for strings returned by read
     * operations on text content (e.g. MessagePart::getContentResourceHandle,
     * getContent, etc...)
     * 
     * @param string $charset
     */
    public static function setDefaultCharset($charset)
    {
        self::$defaultCharset = $charset;
    }
    
    /**
     * Returns the default charset that will be used by MMP strings returned.
     * 
     * @return string
     */
    public static function getDefaultCharset()
    {
        return self::$defaultCharset;
    }
    
    /**
     * Parses the passed stream handle into a ZBateson\MailMimeParser\Message
     * object and returns it.
     * 
     * Internally, the message is first copied to a temp stream (with php://temp
     * which may keep it in memory or write it to disk) and its stream is used.
     * That way if the message is too large to hold in memory it can be written
     * to a temporary file if need be.
     * 
     * @param resource|string $handleOrString the resource handle to the input
     *        stream of the mime message, or a string containing a mime message
     * @return \ZBateson\MailMimeParser\Message
     */
    public function parse($handleOrString)
    {
        $stream = Psr7\stream_for($handleOrString);
        $copy = Psr7\stream_for(fopen('php://temp', 'r+'));

        Psr7\copy_to_stream($stream, $copy);
        $copy->rewind();
        // don't close it when $stream gets destroyed
        $stream->detach();
        $parser = $this->di->newMessageParser();
        return $parser->parse(StreamWrapper::getResource($copy));
    }
}
