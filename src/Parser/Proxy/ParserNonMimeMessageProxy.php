<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

/**
 * Description of ParserUUEncodedPartProxy
 *
 * @author Zaahid Bateson
 */
class ParserNonMimeMessageProxy extends ParserMessageProxy
{
    protected $nextPartStart = null;
    protected $nextPartMode = null;
    protected $nextPartFilename = null;

    public function getNextPartStart()
    {
        return $this->nextPartStart;
    }

    public function getNextPartMode()
    {
        return $this->nextPartMode;
    }

    public function getNextPartFilename()
    {
        return $this->nextPartFilename;
    }

    public function setNextPartStart($nextPartStart)
    {
        $this->nextPartStart = $nextPartStart;
    }

    public function setNextPartMode($nextPartMode)
    {
        $this->nextPartMode = $nextPartMode;
    }

    public function setNextPartFilename($nextPartFilename)
    {
        $this->nextPartFilename = $nextPartFilename;
    }

    public function clearNextPart()
    {
        $this->nextPartStart = null;
        $this->nextPartMode = null;
        $this->nextPartFilename = null;
    }
}
