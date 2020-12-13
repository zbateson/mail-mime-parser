<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\CachingStream;

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
     * @var string the default charset used to encode strings (or string content
     *      like streams) returned by MailMimeParser (for e.g. the string
     *      returned by calling $message->getTextContent()).
     */
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * @var \ZBateson\MailMimeParser\Container dependency injection container
     */
    protected $di;
    
    /**
     * Sets up the parser.
     *
     * @param Container $di pass a Container object to use it for
     *        initialization.
     */
    public function __construct(array $providers = [], Container $di = null)
    {
        if ($di === null) {
            $di = new Container();
            $di->register(new DefaultProvider());
        }
        foreach ($providers as $provider) {
            $di->register($provider);
        }
        $this->di = $di;
    }

    /**
     * Parses the passed stream handle or string into a
     * ZBateson\MailMimeParser\Message object and returns it.
     *
     * By default, the passed stream is in 'attached' mode, and will close when
     * the Message is destroyed.  Pass TRUE as the second argument to keep the
     * stream open.  In either case, the passed stream must remain open so long
     * as the Message object exists.
     *
     * @param resource|string $handleOrString the resource handle to the input
     *        stream of the mime message, or a string containing a mime message
     * @param bool $detached set to true to keep the stream open
     * @return \ZBateson\MailMimeParser\Message
     */
    public function parse($handleOrString, $detached = false)
    {
        $stream = Psr7\stream_for(
            $handleOrString,
            [ 'metadata' => [ 'mmp-detached-stream' => $detached ] ]
        );
        if (!$stream->isSeekable()) {
            $stream = new CachingStream($stream);
        }
        $parser = $this->di['\ZBateson\MailMimeParser\Message\MessageParser'];
        return $parser->parse($stream);
    }
}
