<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\PartFactory;

/**
 * Description of HeaderFactory
 *
 * @author Zaahid Bateson
 */
class HeaderFactory
{
    protected $consumerService;
    protected $partFactory;
    protected $types = [
        /*'ZBateson\MailMimeParser\Header\StructuredHeader' => [
            'message-id',
            'resent-message-id',
            'content-id',
            'in-reply-to'
        ],*/
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
        'ZBateson\MailMimeParser\Header\ValueParametersHeader' => [
            'content-type',
            'content-disposition',
        ]
    ];
    protected $genericType = 'ZBateson\MailMimeParser\Header\GenericHeader';
    
    public function __construct(ConsumerService $consumerService, PartFactory $partFactory)
    {
        $this->consumerService = $consumerService;
        $this->partFactory = $partFactory;
    }
    
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
    
    public function newInstance($name, $value)
    {
        $class = $this->getClassFor($name);
        return new $class($this->consumerService, $this->partFactory, $name, $value);
    }
}
