<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderContainer;
use ZBateson\MailMimeParser\Parser\ParserProxy;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Description of DefaultProvider
 *
 * @author Zaahid Bateson
 */
class DefaultProvider implements ServiceProviderInterface {

    public function register(Container $pimple)
    {
        $pimple['\ZBateson\MailMimeParser\Message\PartStreamContainer'] = $pimple->factory(function() use ($pimple) {
            $factory = $pimple['\ZBateson\MailMimeParser\Message\Factory\PartStreamContainerFactory'];
            return $factory->newInstance();
        });
        $pimple['\ZBateson\MailMimeParser\Header\HeaderContainer'] = $pimple->factory(function() use ($pimple) {
            return new HeaderContainer($pimple['\ZBateson\MailMimeParser\Header\HeaderFactory']);
        });
        $pimple['\ZBateson\MailMimeParser\Message\PartChildrenContainer'] = $pimple->factory(function() use ($pimple) {
            return new PartChildrenContainer();
        });
        $pimple['ZBateson\MailMimeParser\Parser\ParserProxy'] = $pimple->factory(function() use ($pimple) {
            return new ParserProxy(
                $pimple['\ZBateson\MailMimeParser\Parser\BaseParser'],
                $pimple['\ZBateson\MailMimeParser\Stream\StreamFactory']
            );
        });
    }
}
