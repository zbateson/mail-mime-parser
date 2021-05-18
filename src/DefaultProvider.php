<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Message\HeaderContainer;
use ZBateson\MailMimeParser\Message\PartChildrenContainer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Default Pimple\ServiceProviderInterface defining classes that require
 * factories or special configuration on initialization.
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
        $pimple['\ZBateson\MailMimeParser\Message\HeaderContainer'] = $pimple->factory(function() use ($pimple) {
            return new HeaderContainer($pimple['\ZBateson\MailMimeParser\Header\HeaderFactory']);
        });
        $pimple['\ZBateson\MailMimeParser\Message\PartChildrenContainer'] = $pimple->factory(function() use ($pimple) {
            return new PartChildrenContainer();
        });

        // This might need to move somewhere else. 'extend' doesn't work because BaseParser
        // is a dependency of the other parsers, and using extend causes it to be called
        // recursively when trying to create an instance of a parser and BaseParser
        // is not yet registered on Pimple\Container
        $baseParser = $pimple['\ZBateson\MailMimeParser\Parser\BaseParser'];
        $baseParser->addContentParser($pimple['\ZBateson\MailMimeParser\Parser\MimeContentParser']);
        $baseParser->addContentParser($pimple['\ZBateson\MailMimeParser\Parser\NonMimeParser']);
        $baseParser->addChildParser($pimple['\ZBateson\MailMimeParser\Parser\MultipartChildrenParser']);
        $baseParser->addChildParser($pimple['\ZBateson\MailMimeParser\Parser\NonMimeParser']);
    }
}
