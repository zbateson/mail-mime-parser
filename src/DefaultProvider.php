<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Default Pimple\ServiceProviderInterface defining classes that require
 * factories or special configuration on initialization.
 *
 * @author Zaahid Bateson
 */
class DefaultProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['\ZBateson\MailMimeParser\Message\PartStreamContainer'] = $pimple->factory(function() use ($pimple) {
            $factory = $pimple['\ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory'];
            return $factory->newInstance();
        });
        $pimple['\ZBateson\MailMimeParser\Message\PartHeaderContainer'] = $pimple->factory(function() use ($pimple) {
            return new PartHeaderContainer($pimple['\ZBateson\MailMimeParser\Header\HeaderFactory']);
        });
        $pimple['\ZBateson\MailMimeParser\Message\PartChildrenContainer'] = $pimple->factory(function() use ($pimple) {
            return new PartChildrenContainer();
        });
    }
}
