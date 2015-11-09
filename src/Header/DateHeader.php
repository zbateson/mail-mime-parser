<?php
namespace ZBateson\MailMimeParser\Header;

/**
 * Description of DateHeader
 *
 * @author Zaahid Bateson
 */
class DateHeader extends Header
{
    protected $date;
    
    protected function setupConsumer()
    {
        $this->consumer = $this->consumerService->getDateConsumer();
    }
    
    protected function parseValue()
    {
        parent::parseValue();
        if (!empty($this->part)) {
            $this->date = $this->part->date;
        }
    }
}
