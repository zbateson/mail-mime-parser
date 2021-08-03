<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

/**
 * Description of MimePartProxy
 *
 * @author Zaahid Bateson
 */
class ParserMessageProxy extends ParserMimePartProxy
{
    /**
     * Returns true if either a Content-Type or Mime-Version header are defined
     * in this PartBuilder's headers.
     *
     * @return boolean
     */
    public function isMimeMessagePart()
    {
        return ($this->headerContainer->exists('Content-Type') ||
            $this->headerContainer->exists('Mime-Version'));
    }
}
