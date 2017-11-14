<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Responsible for creating PartStreamFilterManager instances.
 *
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerFactory
{
    /**
     * Constructs a new PartStreamFilterManager object and returns it.
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager
     */
    public function newInstance()
    {
        return new PartStreamFilterManager();
    }
}
