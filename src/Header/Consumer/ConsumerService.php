<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use ZBateson\MailMimeParser\Container\IService;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\ReceivedDateConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Simple service provider for consumer singletons.
 *
 * @author Zaahid Bateson
 */
class ConsumerService implements IService
{
    /**
     * @var \ZBateson\MailMimeParser\Header\Part\HeaderPartFactory the
     * HeaderPartFactory instance used to create HeaderParts.
     */
    protected $partFactory;

    /**
     * @var \ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory used for
     *      GenericConsumer instances.
     */
    protected $mimeLiteralPartFactory;

    /**
     * @var array<string,DomainConsumerService[]|GenericReceivedConsumerService[]|ReceivedDateConsumer[]>
     *      an array of sub-received header consumer instances.
     */
    protected $receivedConsumers = [
        'from' => null,
        'by' => null,
        'via' => null,
        'with' => null,
        'id' => null,
        'for' => null,
        'date' => null
    ];

    public function __construct(HeaderPartFactory $partFactory, MimeLiteralPartFactory $mimeLiteralPartFactory)
    {
        $this->partFactory = $partFactory;
        $this->mimeLiteralPartFactory = $mimeLiteralPartFactory;
    }

    /**
     * Returns the AddressBaseConsumer singleton instance.
     *
     * @return AddressBaseConsumerService
     */
    public function getAddressBaseConsumer()
    {
        return AddressBaseConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the AddressConsumer singleton instance.
     *
     * @return AddressConsumerService
     */
    public function getAddressConsumer()
    {
        return AddressConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the AddressGroupConsumer singleton instance.
     *
     * @return AddressGroupConsumerService
     */
    public function getAddressGroupConsumer()
    {
        return AddressGroupConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the AddressEmailConsumer singleton instance.
     *
     * @return AddressEmailConsumerService
     */
    public function getAddressEmailConsumer()
    {
        return AddressEmailConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the CommentConsumer singleton instance.
     *
     * @return CommentConsumerService
     */
    public function getCommentConsumer()
    {
        return CommentConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the GenericConsumer singleton instance.
     *
     * @return GenericConsumerService
     */
    public function getGenericConsumer()
    {
        return GenericConsumerService::getInstance($this, $this->mimeLiteralPartFactory);
    }

    /**
     * Returns the SubjectConsumer singleton instance.
     *
     * @return SubjectConsumerService
     */
    public function getSubjectConsumer()
    {
        return SubjectConsumerService::getInstance($this, $this->mimeLiteralPartFactory);
    }

    /**
     * Returns the QuotedStringConsumer singleton instance.
     *
     * @return QuotedStringConsumerService
     */
    public function getQuotedStringConsumer()
    {
        return QuotedStringConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the DateConsumer singleton instance.
     *
     * @return DateConsumerService
     */
    public function getDateConsumer()
    {
        return DateConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the ParameterConsumer singleton instance.
     *
     * @return ParameterConsumerService
     */
    public function getParameterConsumer()
    {
        return ParameterConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the consumer instance corresponding to the passed part name of a
     * Received header.
     *
     * @return AbstractConsumerService
     */
    public function getSubReceivedConsumer(string $partName)
    {
        if (empty($this->receivedConsumers[$partName])) {
            $consumer = null;
            if ($partName === 'from' || $partName === 'by') {
                $consumer = new DomainConsumerService($this, $this->partFactory, $partName);
            } elseif ($partName === 'date') {
                $consumer = new ReceivedDateConsumerService($this, $this->partFactory);
            } else {
                $consumer = new GenericReceivedConsumerService($this, $this->partFactory, $partName);
            }
            $this->receivedConsumers[$partName] = $consumer;
        }
        return $this->receivedConsumers[$partName];
    }

    /**
     * Returns the ReceivedConsumer singleton instance.
     *
     * @return ReceivedConsumerService
     */
    public function getReceivedConsumer()
    {
        return ReceivedConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the IdConsumer singleton instance.
     *
     * @return IdConsumerService
     */
    public function getIdConsumer()
    {
        return IdConsumerService::getInstance($this, $this->partFactory);
    }

    /**
     * Returns the IdBaseConsumer singleton instance.
     *
     * @return IdBaseConsumerService
     */
    public function getIdBaseConsumer()
    {
        return IdBaseConsumerService::getInstance($this, $this->partFactory);
    }
}
