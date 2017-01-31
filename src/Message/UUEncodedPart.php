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
 * A specialized NonMimePart representing a uuencoded part.
 * 
 * This represents part of a message that is not a mime message.  A multi-part
 * mime message may have a part with a Content-Transfer-Encoding of x-uuencode
 * but that would be represented by a normal MimePart.
 * 
 * UUEncodedPart extends NonMimePart to return a Content-Transfer-Encoding of
 * x-uuencode, a Content-Type of application-octet-stream, and a
 * Content-Disposition of 'attachment'.  It also expects a mode and filename to
 * initialize it, and adds 'filename' parts to the Content-Disposition and
 * 'name' to Content-Type.
 * 
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class UUEncodedPart extends NonMimePart
{
    /**
     * @var int the unix file permission
     */
    protected $mode = null;
    
    /**
     * @var string the name of the file in the uuencoding 'header'.
     */
    protected $filename = null;
    
    /**
     * Initiates the UUEncodedPart with the passed mode and filename.
     * 
     * @param HeaderFactory $headerFactory
     * @param MimePartWriter $partWriter
     * @param int $mode the unix file mode
     * @param string $filename the filename
     */
    public function __construct(
        HeaderFactory $headerFactory,
        MimePartWriter $partWriter,
        $mode,
        $filename
    ) {
        parent::__construct($headerFactory, $partWriter);
        $this->mode = $mode;
        $this->filename = $filename;
        
        $this->setRawHeader(
            'Content-Type',
            'application/octet-stream; name="' . addcslashes($filename, '"') . '"'
        );
        $this->setRawHeader(
            'Content-Disposition',
            'attachment; filename="' . addcslashes($filename, '"') . '"'
        );
        $this->setRawHeader('Content-Transfer-Encoding', 'x-uuencode');
    }
    
    /**
     * Returns the file mode included in the uuencoded header for this part.
     * 
     * @return int
     */
    public function getUnixFileMode()
    {
        return $this->mode;
    }
    
    /**
     * Returns the filename included in the uuencoded header for this part.
     * 
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
