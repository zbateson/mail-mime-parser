<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Util;

/**
 * Holds global configuration options.
 *
 * @author Zaahid Bateson
 */
class Configuration
{
    /**
     * @var string the charset used for returning content strings, e.g.
     *      $part->getContent or $part->getResourceContentHandle
     */
    private $contentCharset;
    
    /**
     * Sets the charset of returned strings in content returned from calls to
     * MimePart (e.g. getContent or getContentResourceHandle)
     * 
     * @param string $contentCharset
     */
    public function setContentCharset($contentCharset)
    {
        $this->contentCharset = $contentCharset;
    }

    /**
     * Returns the charset for returned strings in content returned from calls
     * to MimePart (e.g. getContent or getContentResourceHandle)
     * 
     * @return string
     */
    public function getContentCharset()
    {
        return $this->contentCharset;
    }
}
