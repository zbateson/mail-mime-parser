<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Represents part of a non-mime message.  The part could either be a plain text
 * part or a uuencoded attachment and could be extended for other pre-mime
 * message encoding types.
 * 
 * This allows clients to handle all messages as mime messages by providing a
 * Content-Type header.  NonMimePart returns text/plain.
 * 
 * @author Zaahid Bateson
 */
class NonMimePart extends MessagePart
{
    /**
     * Sets up class dependencies.
     * 
     * @param string $messageObjectId
     * @param PartBuilder $partBuilder
     */
    public function __construct($messageObjectId, PartBuilder $partBuilder)
    {
        parent::__construct($messageObjectId, $partBuilder);
    }
    
    /**
     * Returns true.
     * 
     * @return bool
     */
    public function isTextPart()
    {
        return true;
    }
    
    /**
     * Returns text/plain
     * 
     * @return string
     */
    public function getContentType()
    {
        return 'text/plain';
    }
    
    /**
     * Returns 'inline'.
     * 
     * @return string
     */
    public function getContentDisposition()
    {
        return 'inline';
    }
    
    /**
     * Returns '7bit'.
     * 
     * @return string
     */
    public function getContentTransferEncoding()
    {
        return '7bit';
    }
    
    /**
     * Returns false.
     * 
     * @return bool
     */
    public function isMime()
    {
        return false;
    }
}
