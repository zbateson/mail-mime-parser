<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Description of PartChildContained
 *
 * @author Zaahid Bateson
 */
class PartChildContained
{
    /**
     * @var IMessagePart
     */
    protected $part;

    /**
     * @var PartChildrenContainer
     */
    protected $container;

    public function __construct(IMessagePart $part, PartChildrenContainer $container = null)
    {
        $this->part = $part;
        $this->container = $container;
    }

    public function getPart()
    {
        return $this->part;
    }

    public function getContainer()
    {
        return $this->container;
    }
}
