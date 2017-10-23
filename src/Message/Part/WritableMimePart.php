<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Writer\MimePartWriter;

/**
 * Description of WritableMimePart
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class WritableMimePart
{
    /**
     * @var \ZBateson\MailMimeParser\Message\Writer\MimePartWriter the part
     *      writer for this MimePart
     */
    protected $partWriter = null;

    /**
     * Registers the passed part as a child of the current part.
     * 
     * If the $position parameter is non-null, adds the part at the passed
     * position index.
     *
     * @param \ZBateson\MailMimeParser\Message\Part\MimePart $part
     * @param int $position
     */
    public function addPart(MimePart $part, $position = null)
    {
        if ($part !== $this) {
            $part->setParent($this);
            array_splice($this->parts, ($position === null) ? count($this->parts) : $position, 0, [ $part ]);
        }
    }

    /**
     * Removes the child part from this part and returns its position or
     * null if it wasn't found.
     * 
     * Note that if the part is not a direct child of this part, the returned
     * position is its index within its parent (calls removePart on its direct
     * parent).
     *
     * @param \ZBateson\MailMimeParser\Message\Part\MimePart $part
     * @return int or null if not found
     */
    public function removePart(MimePart $part)
    {
        $parent = $part->getParent();
        if ($this !== $parent && $parent !== null) {
            return $parent->removePart($part);
        } else {
            $position = array_search($part, $this->parts, true);
            if ($position !== false) {
                array_splice($this->parts, $position, 1);
                return $position;
            }
        }
        return null;
    }

    /**
     * Removes all parts that are matched by the passed PartFilter.
     * 
     * @param \ZBateson\MailMimeParser\Message\PartFilter $filter
     */
    public function removeAllParts(PartFilter $filter = null)
    {
        foreach ($this->getAllParts($filter) as $part) {
            $this->removePart($part);
        }
    }


    /**
     * Attaches the resource handle for the part's content.  The attached handle
     * is closed when the MimePart object is destroyed.
     *
     * @param resource $contentHandle
     */
    public function attachContentResourceHandle($contentHandle)
    {
        if ($this->handle !== null && $this->handle !== $contentHandle) {
            fclose($this->handle);
        }
        $this->handle = $contentHandle;
    }
    
    /**
     * Attaches the resource handle representing the original stream that
     * created this part (including any sub-parts).  The attached handle is
     * closed when the MimePart object is destroyed.
     * 
     * This stream is not modified or changed as the part is changed and is only
     * set during parsing in MessageParser.
     *
     * @param resource $handle
     */
    public function attachOriginalStreamHandle($handle)
    {
        if ($this->originalStreamHandle !== null && $this->originalStreamHandle !== $handle) {
            fclose($this->originalStreamHandle);
        }
        $this->originalStreamHandle = $handle;
    }
    
    
    /**
     * Detaches the content resource handle from this part but does not close
     * it.
     */
    protected function detachContentResourceHandle()
    {
        $this->handle = null;
    }

    /**
     * Sets the content of the part to the passed string (effectively creates
     * a php://temp stream with the passed content and calls
     * attachContentResourceHandle with the opened stream).
     *
     * @param string $string
     */
    public function setContent($string)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $string);
        rewind($handle);
        $this->attachContentResourceHandle($handle);
    }

    
    /**
     * Adds a header with the given $name and $value.
     *
     * Creates a new \ZBateson\MailMimeParser\Header\AbstractHeader object and
     * registers it as a header.
     *
     * @param string $name
     * @param string $value
     */
    public function setRawHeader($name, $value)
    {
        $this->headers[strtolower($name)] = $this->headerFactory->newInstance($name, $value);
    }

    /**
     * Removes the header with the given name
     *
     * @param string $name
     */
    public function removeHeader($name)
    {
        unset($this->headers[strtolower($name)]);
    }

    
    /**
     * Sets the parent part.
     *
     * @param \ZBateson\MailMimeParser\Message\Part\MessagePart $part
     */
    public function setParent(MessagePart $part)
    {
        $this->parent = $part;
    }
}
