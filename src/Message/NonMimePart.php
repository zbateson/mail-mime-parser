<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\Writer\MimePartWriter;

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
class NonMimePart extends MimePart
{
    /**
     * Sets up a default Content-Type header of text/plain.
     * 
     * @param HeaderFactory $headerFactory
     * @param MimePartWriter $partWriter
     */
    public function __construct(HeaderFactory $headerFactory, MimePartWriter $partWriter)
    {
        parent::__construct($headerFactory, $partWriter);
        $this->setRawHeader('Content-Type', 'text/plain');
    }
}
