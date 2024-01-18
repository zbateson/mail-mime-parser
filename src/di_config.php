<?php

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Header\Consumer\AddressBaseConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\DateConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\GenericConsumerMimeLiteralPartService;
use ZBateson\MailMimeParser\Header\Consumer\IdBaseConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ParameterConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ReceivedConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\SubjectConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\DomainConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\Received\GenericReceivedConsumerService;

return [
    LoggerInterface::class => DI\autowire(NullLogger::class),

    'fromDomainConsumerService' => DI\autowire(DomainConsumerService::class)
        ->constructorParameter('partName', 'from'),
    'byDomainConsumerService' => DI\autowire(DomainConsumerService::class)
        ->constructorParameter('partName', 'by'),
    'viaGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'via'),
    'withGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'with'),
    'idGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'id'),
    'forGenericReceivedConsumerService' => DI\autowire(GenericReceivedConsumerService::class)
        ->constructorParameter('partName', 'for'),
    ReceivedConsumerService::class => DI\autowire()
        ->constructor(
            fromDomainConsumerService: DI\get('fromDomainConsumerService'),
            byDomainConsumerService: DI\get('byDomainConsumerService'),
            viaGenericReceivedConsumerService: DI\get('viaGenericReceivedConsumerService'),
            withGenericReceivedConsumerService: DI\get('withGenericReceivedConsumerService'),
            idGenericReceivedConsumerService: DI\get('idGenericReceivedConsumerService'),
            forGenericReceivedConsumerService: DI\get('forGenericReceivedConsumerService')
        ),
];
