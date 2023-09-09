<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Pimple\ServiceProviderInterface;

/**
 * 
 * @author Zaahid Bateson
 */
interface IExtension
{
    public function getServiceProviderInterface() : ServiceProviderInterface;
}
