<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Description of MessagePartDecorator
 *
 * @author Zaahid Bateson
 */
class MessagePartDecoratorTrait
{
    public function __construct(IMessagePart $messagePart)
    {
        $this->messagePart = $messagePart;
    }

    public function __call($method, array $args)
    {
        $result = call_user_func_array([$this->messagePart, $method], $args);
        return ($result === $this->messagePart) ? $this : $result;
    }
}
