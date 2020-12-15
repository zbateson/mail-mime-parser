<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Description of MessageWrapper
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class PartDecorator {
    /**
     * @var MessagePart
     */
    protected $part;

    public function __construct(MessagePart $part)
    {
        $this->part = $part;
    }

    public function __call($name, $args)
    {
        call_user_func_array([ $this->part, $name ], $args);
    }
}
