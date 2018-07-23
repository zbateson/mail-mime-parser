<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartFactoryService;

/**
 * Responsible for creating MessageHelper instances.
 *
 * @author Zaahid Bateson
 */
class MessageHelperFactory
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @param PartBuilderFactory $partBuilderFactory
     */
    public function __construct(PartBuilderFactory $partBuilderFactory)
    {
        $this->partBuilderFactory = $partBuilderFactory;
    }

    /**
     * Constructs a new MessageHelper object and returns it
     * 
     * @param PartFactoryService $partFactoryService
     * @return MessageHelper
     */
    public function newMessageHelper(PartFactoryService $partFactoryService)
    {
        return new MessageHelper(
            $partFactoryService->getMimePartFactory(),
            $partFactoryService->getUUEncodedPartFactory(),
            $this->partBuilderFactory
        );
    }
}
