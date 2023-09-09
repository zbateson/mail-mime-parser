<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Container\IService;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;

/**
 * Creates PartChildrenContainer instances.
 *
 * @author Zaahid Bateson
 */
class PartChildrenContainerFactory implements IService
{
    public function newInstance()
    {
        return new PartChildrenContainer();
    }
}
