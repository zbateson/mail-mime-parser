<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Pimple\ServiceProviderInterface;
use ZBateson\MailMimeParser\Parser\MessageParserService;

/**
 * Parses a MIME message into an {@see IMessage} object.
 *
 * The class sets up the Pimple dependency injection container with the ability
 * to override and/or provide specialized provider
 * {@see https://pimple.symfony.com/ \Pimple\ServiceProviderInterface}
 * classes to extend default classes used by MailMimeParser.
 *
 * To invoke, call parse on a MailMimeParser object.
 *
 * ```php
 * $parser = new MailMimeParser();
 * // the resource is attached due to the second parameter being true and will
 * // be closed when the returned IMessage is destroyed
 * $message = $parser->parse(fopen('path/to/file.txt'), true);
 * // use $message here
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
    public const DEFAULT_CHARSET = 'UTF-8';

    /**
     * @var ServiceLocator The instance's dependency injection container.
     */
    protected $container = null;

    /**
     * @var MessageParserService for parsing messages
     */
    protected $messageParser;

    /**
     * Registers a
     *
     * @param ServiceProviderInterface[] $serviceProviders
     */
    public static function registerGlobalServiceProviders(array $serviceProviders) : void
    {
        ServiceLocator::setGlobalServiceProviders($serviceProviders);
    }

    /**
     * Registers the provided logger globally
     */
    public static function setGlobalLogger(LoggerInterface $logger) : void
    {
        ServiceLocator::setGlobalLogger($logger);
    }

    /**
     * Provide custom ServiceProviderInterface objects to customize dependency
     * injection for email parsing, or provide a custom logger for the new
     * instance only.
     *
     * Note 1: providing an array of service providers creates a dependency
     * injection container <i>without</i> using any previously registered global
     * service providers.
     *
     * Note 2: this only affects instances created through this instance of the
     * MailMimeParser, or the container itself.  Calling 'new MimePart()'
     * directly for instance, would use the global service locator to setup any
     * dependencies MimePart needs.  This applies to a provided $logger too --
     * it would only affect instances of objects created through the provided
     * MailMimeParser.
     *
     * @see MailMimeParser::registerGlobalServiceProviders() to register service
     *      providers globally
     * @see MailMimeParser::setGlobalLogger() to set a global logger
     * @param ?ServiceProviderInterface[] $serviceProviders
     * @param ?LoggerInterface $logger
     */
    public function __construct(?array $serviceProviders = null, ?LoggerInterface $logger = null)
    {
        if ($serviceProviders !== null || $logger !== null) {
            $this->container = ServiceLocator::newInstance($logger, $serviceProviders);
        } else {
            $this->container = ServiceLocator::getGlobalInstance();
        }
        $this->messageParser = $this->container[MessageParserService::class];
    }

    /**
     * Parses the passed stream handle or string into an {@see IMessage} object
     * and returns it.
     *
     * If the passed $resource is a resource handle or StreamInterface, the
     * resource must remain open while the returned IMessage object exists.
     * Pass true as the second argument to have the resource attached to the
     * IMessage and closed for you when it's destroyed, or pass false to
     * manually close it if it should remain open after the IMessage object is
     * destroyed.
     *
     * @param resource|StreamInterface|string $resource The resource handle to
     *        the input stream of the mime message, or a string containing a
     *        mime message.
     * @param bool $attached pass true to have it attached to the returned
     *        IMessage and destroyed with it.
     * @return IMessage
     */
    public function parse($resource, $attached) : IMessage
    {
        $stream = Utils::streamFor(
            $resource,
            ['metadata' => ['mmp-detached-stream' => ($attached !== true)]]
        );
        if (!$stream->isSeekable()) {
            $stream = new CachingStream($stream);
        }
        return $this->messageParser->parse($stream);
    }
}
