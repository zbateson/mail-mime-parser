<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use ReflectionClass;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Constructs various IHeader types depending on the type of header passed.
 *
 * If the passed header resolves to a specific defined header type, it is parsed
 * as such.  Otherwise, a GenericHeader is instantiated and returned.  Headers
 * are mapped as follows:
 *
 *  - {@see AddressHeader}: From, To, Cc, Bcc, Sender, Reply-To, Resent-From,
 *    Resent-To, Resent-Cc, Resent-Bcc, Resent-Reply-To, Return-Path,
 *    Delivered-To
 *  - {@see DateHeader}: Date, Resent-Date, Delivery-Date, Expires, Expiry-Date,
 *    Reply-By
 *  - {@see ParameterHeader}: Content-Type, Content-Disposition, Received-SPF,
 *    Authentication-Results, DKIM-Signature, Autocrypt
 *  - {@see SubjectHeader}: Subject
 *  - {@see IdHeader}: Message-ID, Content-ID, In-Reply-To, References
 *  - {@see ReceivedHeader}: Received
 *
 * @author Zaahid Bateson
 */
class HeaderFactory
{
    #[Inject]
    protected LoggerInterface $logger;

    /**
     * @var IConsumerService[] array of available consumer service classes
     */
    protected array $consumerServices;

    /**
     * @var MimeLiteralPartFactory for mime decoding.
     */
    protected MimeLiteralPartFactory $mimeLiteralPartFactory;

    /**
     * @var string[][] maps IHeader types to headers.
     */
    protected $types = [
        AddressHeader::class => [
            'from',
            'to',
            'cc',
            'bcc',
            'sender',
            'replyto',
            'resentfrom',
            'resentto',
            'resentcc',
            'resentbcc',
            'resentreplyto',
            'returnpath',
            'deliveredto',
        ],
        DateHeader::class => [
            'date',
            'resentdate',
            'deliverydate',
            'expires',
            'expirydate',
            'replyby',
        ],
        ParameterHeader::class => [
            'contenttype',
            'contentdisposition',
            'receivedspf',
            'authenticationresults',
            'dkimsignature',
            'autocrypt',
        ],
        SubjectHeader::class => [
            'subject',
        ],
        IdHeader::class => [
            'messageid',
            'contentid',
            'inreplyto',
            'references'
        ],
        ReceivedHeader::class => [
            'received'
        ]
    ];

    /**
     * @var string Defines the generic IHeader type to use for headers that
     *      aren't mapped in $types
     */
    protected $genericType = GenericHeader::class;

    /**
     * Instantiates member variables with the passed objects.
     *
     */
    public function __construct(array $consumerServices, MimeLiteralPartFactory $mimeLiteralPartFactory)
    {
        $this->consumerServices = $consumerServices;
        $this->mimeLiteralPartFactory = $mimeLiteralPartFactory;
        $this->logger = new NullLogger();
    }

    /**
     * Returns the string in lower-case, and with non-alphanumeric characters
     * stripped out.
     *
     * @param string $header The header name
     * @return string The normalized header name
     */
    public function getNormalizedHeaderName(string $header) : string
    {
        return \preg_replace('/[^a-z0-9]/', '', \strtolower($header));
    }

    /**
     * Returns the name of an IHeader class for the passed header name.
     *
     * @param string $name The header name.
     * @return string The Fully Qualified class name.
     */
    private function getClassFor(string $name) : string
    {
        $test = $this->getNormalizedHeaderName($name);
        foreach ($this->types as $class => $matchers) {
            foreach ($matchers as $matcher) {
                if ($test === $matcher) {
                    return $class;
                }
            }
        }
        return $this->genericType;
    }

    /**
     * Creates an IHeader instance for the passed header name and value, and
     * returns it.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return IHeader The created header object.
     */
    public function newInstance(string $name, string $value)
    {
        $class = $this->getClassFor($name);
        $this->logger->debug(
            'Creating ${class} for header with name "${name}" and value "${value}"',
            [ 'class' => $class, 'name' => $name, 'value' => $value ]
        );
        return $this->newInstanceOf($name, $value, $class);
    }

    /**
     * Creates an IHeader instance for the passed header name and value, and
     * returns it.
     *
     * @param $name The header name.
     * @param $value The header value.
     * @return The created header object.
     */
    public function newInstanceOf(string $name, string $value, string $iHeaderClass) : IHeader
    {
        $ref = new ReflectionClass($iHeaderClass);
        $params = $ref->getConstructor()->getParameters();
        if ($ref->isSubclassOf(MimeEncodedHeader::class)) {
            return new $iHeaderClass(
                $this->mimeLiteralPartFactory,
                $this->consumerServices[$params[1]->getClass()->getName()],
                $name,
                $value
            );
        }
        return new $iHeaderClass(
            $this->consumerServices[$params[0]->getClass()->getName()],
            $name,
            $value
        );
    }
}
