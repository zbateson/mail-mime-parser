<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use ReflectionClass;

/**
 * Description of DefaultProvider
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class DefaultProvider implements ServiceProviderInterface {

    public function register(Container $pimple)
    {
        $pimple->extend('\ZBateson\MailMimeParser\Message\Parser\BaseParser', function($parser, $c) {
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Message\Parser\HeaderParser']);
            return $parser;
        });
        $pimple->extend('\ZBateson\MailMimeParser\Message\Parser\HeaderParser', function($parser, $c) {
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Message\Parser\MimeParser']);
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Message\Parser\NonMimeParser']);
            return $parser;
        });
        $pimple->extend('\ZBateson\MailMimeParser\Message\Parser\MimeParser', function($parser, $c) {
            $parser->addSubParser($c['\ZBateson\MailMimeParser\Message\Parser\MultipartParser']);
            return $parser;
        });
    }
}
