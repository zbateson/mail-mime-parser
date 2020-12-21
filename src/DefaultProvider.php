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
    }
}
