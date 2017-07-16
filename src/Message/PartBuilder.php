<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Description of MimePartBuilder
 *
 * @author Zaahid Bateson
 */
class PartBuilder extends MimePart
{
    public $streamPartReadStartPos = 0;
    public $streamContentReadStartPos = 0;
    public $streamContentReadEndPos = 0;
    public $streamPartReadEndPos = 0;
    
    private $endBoundaryFound = false;
    private $mimeBoundary = false;
    
    /**
     * Registers the passed part as a child of the current part.
     * 
     * If the $position parameter is non-null, adds the part at the passed
     * position index.
     *
     * @param \ZBateson\MailMimeParser\Message\MimePart $part
     * @param int $position
     */
    public function addPart(MimePart $part, $position = null)
    {
        if ($part !== $this) {
            $part->setParent($this);
            array_splice($this->parts, ($position === null) ? count($this->parts) : $position, 0, [ $part ]);
        }
    }
    
    public function getMimeBoundary()
    {
        if ($this->mimeBoundary === false) {
            $this->mimeBoundary = $this->getHeaderParameter('Content-Type', 'boundary');
        }
        return $this->mimeBoundary;
    }
    
    public function setEndBoundary($line)
    {
        $boundary = $this->getMimeBoundary();
        if ($boundary !== null) {
            if ($line === "--$boundary--") {
                $this->endBoundaryFound = true;
                return true;
            } elseif ($line === "--$boundary") {
                return true;
            }
        } elseif ($this->getParent() !== null && $this->getParent()->setEndBoundary($line)) {
            return true;
        }
        return false;
    }
    
    public function isEndBoundaryFound()
    {
        return $this->endBoundaryFound;
    }
}
