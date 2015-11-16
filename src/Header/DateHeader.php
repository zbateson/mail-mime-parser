<?php
namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\Date;

/**
 * Description of DateHeader
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
