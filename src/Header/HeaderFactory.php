<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;

/**
 * Constructs various AbstractHeader types depending on the type of header
 * passed.
 * 
 * If the passed header resolves to a specific defined header type, it is parsed
 * as such.  Otherwise, a GenericHeader is instantiated and returned.  Headers
 * are mapped as follows:
 * 
 * AddressHeader: From, To, Cc, Bcc, Sender, Reply-To, Resent-From, Resent-To,
 * Resent-Cc, Resent-Bcc, Resent-Reply-To
 * DateHeader: Date, Resent-Date, Delivery-Date, Expires, Expiry-Date, Reply-By
 * ParameterHeader: Content-Type, Content-Disposition
 *
 * @author Zaahid Bateson
 */
class HeaderFactory
{
    /**
     * @var ConsumerService the passed ConsumerService providing
     * AbstractConsumer singletons.
     */
    protected $consumerService;
    
    /**
     * @var string[][] maps AbstractHeader types to headers. 
     */
    protected $types = [
        'ZBateson\MailMimeParser\Header\AddressHeader' => [
            'from',
            'to',
            'cc',
            'bcc',
            'sender',
            'reply-to',
            'resent-from',
            'resent-to',
            'resent-cc',
            'resent-bcc',
            'resent-reply-to',
        ],
        'ZBateson\MailMimeParser\Header\DateHeader' => [
            'date',
            'resent-date',
            'delivery-date',
            'expires',
            'expiry-date',
            'reply-by',
        ],
        'ZBateson\MailMimeParser\Header\ParameterHeader' => [
            'content-type',
            'content-disposition',
        ],
        'ZBateson\MailMimeParser\Header\SubjectHeader' => [
            'subject',
        ]
    ];
    
    /**
     * @var string Defines the generic AbstractHeader type to use for headers
     * that aren't mapped in $types
     */
    protected $genericType = 'ZBateson\MailMimeParser\Header\GenericHeader';
    
    /**
     * Instantiates member variables with the passed objects.
     * 
     * @param ConsumerService $consumerService
     */
    public function __construct(ConsumerService $consumerService)
    {
        $this->consumerService = $consumerService;
    }
    
    /**
     * Returns the name of an AbstractHeader class for the passed header name.
     * 
     * @param string $name
     * @return string
     */
    private function getClassFor($name)
    {
        $test = strtolower($name);
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
     * Creates an AbstractHeader instance for the passed header name and value,
     * and returns it.
     * 
     * @param string $name
     * @param string $value
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     */
    public function newInstance($name, $value)
    {
        $class = $this->getClassFor($name);
        return new $class($this->consumerService, $name, $value);
    }
}
