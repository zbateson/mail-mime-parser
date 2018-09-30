<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer;
use ZBateson\MailMimeParser\Header\Part\CommentPart;

/**
 * Represents a Content-ID, Message-ID, In-Reply-To or Reference header.
 *
 * For a multi-id header like In-Reply-To or Reference, all IDs can be retrieved
 * by calling 'getIds()'.  Otherwise, to retrieve the first (or only) ID call
 * 'getValue()'.
 * 
 * @author Zaahid Bateson
 */
class IdHeader extends GenericHeader
{
    /**
     * @var string[] an array of ids found. 
     */
    protected $ids = [];

    /**
     * Returns an IdBaseConsumer.
     *
     * @param ConsumerService $consumerService
     * @return \ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
     */
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getIdBaseConsumer();
    }

    /**
     * Overridden to extract all IDs into ids array.
     *
     * @param AbstractConsumer $consumer
     */
    protected function setParseHeaderValue(AbstractConsumer $consumer)
    {
        parent::setParseHeaderValue($consumer);
        foreach ($this->parts as $part) {
            if (!($part instanceof CommentPart)) {
                $this->ids[] = $part->getValue();
            }
        }
    }

    /**
     * Returns the first parsed ID or null if none exist.
     *
     * @return string|null
     */
    public function getValue()
    {
        if (!empty($this->ids)) {
            return $this->ids[0];
        }
        return null;
    }

    /**
     * Returns all IDs parsed for a multi-id header like Reference or
     * In-Reply-To.
     * 
     * @return string[]
     */
    public function getIds()
    {
        return $this->ids;
    }
}
