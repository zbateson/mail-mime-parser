<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\MailMimeParser;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a plain-text uuencoded part.
 *
 * This represents part of a message that is not a mime message.  A multi-part
 * mime message may have a part with a Content-Transfer-Encoding of x-uuencode
 * but that would be represented by a normal MimePart.
 *
 * IUUEncodedPart returns a Content-Transfer-Encoding of x-uuencode, a
 * Content-Type of application-octet-stream, and a Content-Disposition of
 * 'attachment'.  It also expects a mode and filename to initialize it, and
 * adds 'filename' parts to the Content-Disposition and a 'name' parameter to
 * Content-Type.
 *
 * @author Zaahid Bateson
 */
interface IUUEncodedPart extends IMessagePart
{
    /**
     * Sets the filename included in the uuencoded header.
     *
     * @param string $filename
     */
    public function setFilename($filename);

    /**
     * Returns the file mode included in the uuencoded header for this part.
     *
     * @return int
     */
    public function getUnixFileMode();

    /**
     * Sets the unix file mode for the uuencoded header.
     *
     * @param int $mode
     */
    public function setUnixFileMode($mode);
}
