<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderContainer;
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
        $pimple['\ZBateson\MailMimeParser\Header\HeaderContainer:factory'] = $pimple->factory(function() use ($pimple) {
            return new HeaderContainer($pimple['\ZBateson\MailMimeParser\Header\HeaderFactory']);
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
