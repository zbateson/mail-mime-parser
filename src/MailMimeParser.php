<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\MessageParser;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\CachingStream;

/**
 * Parses a MIME message into a \ZBateson\MailMimeParser\Message object.
 *
 * To invoke, call parse on a MailMimeParser object.
 *
 * ```php
 * $handle = fopen('path/to/file.txt');
 * $parser = new MailMimeParser();
 * $parser->parse($handle);
 * fclose($handle);
 * ```
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
     * @var Container dependency injection container
     */
    protected static $di = null;

    /**
     * @var MessageParser for parsing messages
     */
    protected $messageParser;

    /**
     * Returns the container.
     *
     * @return Container
     */
    public static function getDependencyContainer()
    {
        return static::$di;
    }

    /**
     * (Re)creates the container using the default provider, DefaultProvider,
     * and any additional providers passed in $providers.
     *
     * This is necessary if configuration needs to be reset to parse emails
     * differently.
     *
     * Note that reconfiguring the dependency container can have an affect on
     * existing objects -- for instance if a provider were to override a
     * factory class, and an operation on an existing instance were to try to
     * create an object using that factory class, the new factory class would be
     * returned.  In other words, references to the Container are not maintained
     * in a non-static context separately, so care should be taken when
     * reconfiguring the parser.
     *
     * @param array $providers
     */
    public static function configureDependencyContainer(array $providers = [])
    {
        static::$di = new Container();
        $di = static::$di;
        $di->register(new DefaultProvider());
        foreach ($providers as $provider) {
            $di->register($provider);
        }
    }

    /**
     * Override the dependency container completely.  If multiple configurations
     * are known to be needed, it would be better to keep the different
     * Container configurations and call setDependencyContainer instead of
     * {@see MailMimeParser::configureDependencyContainer}, which instantiates a
     * new {@see Container} on every call.
     *
     * @param Container $di
     */
    public static function setDependencyContainer(Container $di)
    {
        static::$di = $di;
    }
    
    /**
     * Initializes the dependency container if not already initialized.
     *
     * To configure custom {@see https://pimple.symfony.com/ \Pimple\ServiceProviderInterface}
     * objects, call {@see MailMimeParser::configureDependencyContainer()}
     * before creating a MailMimeParser instance.
     */
    public function __construct()
    {
        if (static::$di === null) {
            static::configureDependencyContainer();
        }
        $di = static::$di;
        $this->parser = $di['\ZBateson\MailMimeParser\Parser\MessageParser'];
    }

    /**
     * Parses the passed stream handle or string into a
     * ZBateson\MailMimeParser\IMessage object and returns it.
     *
     * By default, the passed stream is in 'attached' mode, and will close when
     * the Message is destroyed.  Pass TRUE as the second argument to keep the
     * stream open.  In either case, the passed stream must remain open so long
     * as the Message object exists.
     *
     * @param resource|string $handleOrString the resource handle to the input
     *        stream of the mime message, or a string containing a mime message
     * @param bool $detached set to true to keep the stream open
     * @return \ZBateson\MailMimeParser\IMessage
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
        return $this->parser->parse($stream);
    }
}
