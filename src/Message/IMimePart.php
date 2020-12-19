<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Represents a single part of a multi-part mime message.
 *
 * An IMimePart object may have any number of child parts, or may be a child
 * itself with its own parent or parents.
 *
 * The content of the part can be read from its PartStream resource handle,
 * accessible via IMessagePart::getContentResourceHandle.
 *
 * @author Zaahid Bateson
 */
interface IMimePart extends IParentHeaderPart
{
    /**
     * Returns true if this part's mime type is multipart/*
     *
     * @return bool
     */
    public function isMultiPart();

    /**
     * Convenience method to find a part by its Content-ID header.
     *
     * @param string $contentId
     * @return IMessagePart
     */
    public function getPartByContentId($contentId);
}
