<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

use DI\Definition\Helper\AutowireDefinitionHelper;
use DI\Definition\Reference;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\CommentConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\DateConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\GenericConsumerMimeLiteralPartService;
use ZBateson\MailMimeParser\Header\Consumer\IdBaseConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\SubjectConsumerService;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Parser\HeaderParserService;
use ZBateson\MailMimeParser\Parser\MimeParserService;
use ZBateson\MailMimeParser\Parser\NonMimeParserService;
use ZBateson\MailMimeParser\Parser\Part\ParserPartStreamContainerFactory;
use ZBateson\MailMimeParser\Stream\StreamFactory;

return [
    LoggerInterface::class => new AutowireDefinitionHelper(NullLogger::class),

    // only affects reading part content, not for instance decoding mime encoded
    // header parts
    'throwExceptionReadingPartContentFromUnsupportedCharsets' => false,

    // Maximum multipart nesting depth before parsing stops with a recorded error.
    'maxMimePartDepth' => 256,

    // Maximum header count and total header bytes before parsing stops.
    'maxHeaderCount' => 1000,
    'maxHeaderSizeBytes' => 1048576,

    // Maximum number of parts (mime and uu-encoded) in a single message before
    // parsing stops with a recorded error.
    'maxMessagePartCount' => 1000,

    // Maximum number of headers, and total header bytes, read across all parts
    // of a single message before parsing stops with a recorded error.
    'maxMessageHeaderCount' => 50000,
    'maxMessageHeaderSizeBytes' => 8388608,

    // Maximum nesting depth of parenthesized comments in a header value.
    // Comments deeper than this are still parsed over but not kept.
    'maxCommentDepth' => 32,

    // Maximum number of tokens parsed out of a single header's value.  Past
    // this the remainder is kept as one unparsed token and an error recorded.
    'maxHeaderTokenCount' => 20000,

    // Maximum number of header tokens parsed across all headers of a single
    // message.  Headers parsed past this are kept as one unparsed token each
    // and an error recorded.
    'maxMessageHeaderTokenCount' => 250000,

    'fromDomainConsumerService' => (new AutowireDefinitionHelper(DomainConsumerService::class))
        ->constructorParameter('partName', 'from'),
    'byDomainConsumerService' => (new AutowireDefinitionHelper(DomainConsumerService::class))
        ->constructorParameter('partName', 'by'),
    'viaGenericReceivedConsumerService' => (new AutowireDefinitionHelper(GenericReceivedConsumerService::class))
        ->constructorParameter('partName', 'via'),
    'withGenericReceivedConsumerService' => (new AutowireDefinitionHelper(GenericReceivedConsumerService::class))
        ->constructorParameter('partName', 'with'),
    'idGenericReceivedConsumerService' => (new AutowireDefinitionHelper(GenericReceivedConsumerService::class))
        ->constructorParameter('partName', 'id'),
    'forGenericReceivedConsumerService' => (new AutowireDefinitionHelper(GenericReceivedConsumerService::class))
        ->constructorParameter('partName', 'for'),
    CommentConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxCommentDepth: new Reference('maxCommentDepth')
        ),
    AddressBaseConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    DateConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    GenericConsumerMimeLiteralPartService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    IdBaseConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    ParameterConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    SubjectConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    ReceivedConsumerService::class => (new AutowireDefinitionHelper())
        ->constructor(
            fromDomainConsumerService: new Reference('fromDomainConsumerService'),
            byDomainConsumerService: new Reference('byDomainConsumerService'),
            viaGenericReceivedConsumerService: new Reference('viaGenericReceivedConsumerService'),
            withGenericReceivedConsumerService: new Reference('withGenericReceivedConsumerService'),
            idGenericReceivedConsumerService: new Reference('idGenericReceivedConsumerService'),
            forGenericReceivedConsumerService: new Reference('forGenericReceivedConsumerService'),
            maxHeaderTokenCount: new Reference('maxHeaderTokenCount')
        ),
    PartStreamContainer::class => (new AutowireDefinitionHelper())
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: new Reference('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    PartStreamContainerFactory::class => (new AutowireDefinitionHelper())
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: new Reference('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    ParserPartStreamContainerFactory::class => (new AutowireDefinitionHelper())
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: new Reference('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    StreamFactory::class => (new AutowireDefinitionHelper())
        ->constructor(
            throwExceptionReadingPartContentFromUnsupportedCharsets: new Reference('throwExceptionReadingPartContentFromUnsupportedCharsets')
        ),
    PartHeaderContainerFactory::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxMessageHeaderTokenCount: new Reference('maxMessageHeaderTokenCount'),
            maxMessageHeaderCount: new Reference('maxMessageHeaderCount'),
            maxMessageHeaderSizeBytes: new Reference('maxMessageHeaderSizeBytes')
        ),
    HeaderParserService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxHeaderCount: new Reference('maxHeaderCount'),
            maxHeaderSizeBytes: new Reference('maxHeaderSizeBytes')
        ),
    MimeParserService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxMimePartDepth: new Reference('maxMimePartDepth'),
            maxMessagePartCount: new Reference('maxMessagePartCount')
        ),
    NonMimeParserService::class => (new AutowireDefinitionHelper())
        ->constructor(
            maxMessagePartCount: new Reference('maxMessagePartCount')
        ),
];
