<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderContainer;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Description of DefaultProvider
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class DefaultProvider implements ServiceProviderInterface {

    public function register(Container $pimple)
    {
        $pimple['\ZBateson\MailMimeParser\Header\HeaderContainer:factory'] = $pimple->factory(function($c) {
            return new HeaderContainer($c['\ZBateson\MailMimeParser\Header\HeaderFactory']);
        });
        $pimple['\ZBateson\MailMimeParser\Message\PartStreamContainer:factory'] = $pimple->factory(function($c) {
            return new PartStreamContainer($c['\ZBateson\MailMimeParser\Stream\StreamFactory']);
        });

        $pimple->extend('\ZBateson\MailMimeParser\Parser\BaseParser', function($parser, $c) {
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Parser\HeaderParser']);
            return $parser;
        });
        $pimple->extend('\ZBateson\MailMimeParser\Parser\HeaderParser', function($parser, $c) {
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Parser\MimeParser']);
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Parser\NonMimeParser']);
            return $parser;
        });
        $pimple->extend('\ZBateson\MailMimeParser\Parser\MimeParser', function($parser, $c) {
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Parser\MultipartParser']);
            return $parser;
        });
    }
}
