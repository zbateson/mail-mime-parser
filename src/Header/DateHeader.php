<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\Date;

/**
 * Reads a Date value header in eithe RFC 2822 or RFC 822 format.
 * 
 * @author Zaahid Bateson
 */
class DateHeader extends AbstractHeader
{
    /**
     * Returns a DateConsumer.
     * 
     * @param ConsumerService $consumerService
     * @return \ZBateson\MailMimeParser\Header\Consumer\AbstractConsumer
     */
    protected function getConsumer(ConsumerService $consumerService)
    {
        return $consumerService->getDateConsumer();
    }
    
    /**
     * Convenience method returning the part's DateTime object.
     * 
     * @return DateTime
     */
    public function getDateTime()
    {
        if (!empty($this->parts) && $this->parts[0] instanceof Date) {
            return $this->parts[0]->getDateTime();
        }
        return null;
    }
}
