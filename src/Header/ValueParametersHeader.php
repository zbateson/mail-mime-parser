<?php
namespace ZBateson\MailMimeParser\Header;

/**
 * Description of ValueParametersHeader
 *
 * @author Zaahid Bateson
 */
class ValueParametersHeader extends StructuredHeader
{
    protected $params = [];
    
    protected function setupConsumer()
    {
        $this->consumer = $this->consumerService->getParameterConsumer();
        $this->consumers = [
            $this->consumerService->getQuotedStringConsumer(),
            $this->consumerService->getCommentConsumer(),
            $this->consumerService->getParameterConsumer()
        ];
    }
    
    protected function parseValue()
    {
        parent::parseValue();
        if (!empty($this->parts)) {
            $this->value = $this->parts[0]->name;
            $params = array_slice($this->parts, 1);
            foreach ($params as $part) {
                $this->params[$part->name] = $part->value;
            }
        }
    }
}
