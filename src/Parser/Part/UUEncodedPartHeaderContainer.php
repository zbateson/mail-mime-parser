<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Description of UUEncodedPartHeaderContainer
 *
 * @author Zaahid Bateson
 */
class UUEncodedPartHeaderContainer extends PartHeaderContainer
{
    /**
     * @var int the unix file permission
     */
    protected $mode = null;

    /**
     * @var string the name of the file in the uuencoding 'header'.
     */
    protected $filename = null;

    public function getUnixFileMode()
    {
        return $this->mode;
    }

    public function setUnixFileMode($mode)
    {
        $this->mode = $mode;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
}
