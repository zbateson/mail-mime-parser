<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Represents part of a non-mime message.  The part could either be a plain text
 * part or a uuencoded attachment and could be extended for other pre-mime
 * message encoding types.
 * 
 * This allows clients to handle all messages as mime messages by providing a
 * Content-Type header.  NonMimePart returns text/plain.
 * 
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class NonMimePart extends MimePart
{
    /**
     * Sets up a default Content-Type header of text/plain.
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        parent::__construct($headerFactory);
        $this->setRawHeader('Content-Type', 'text/plain');
    }
}
