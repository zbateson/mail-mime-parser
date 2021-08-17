<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Proxy;

use ZBateson\MailMimeParser\Parser\Part\UUEncodedPartHeaderContainer;

/**
 * Description of ParserUUEncodedPartProxy
 *
 * @author Zaahid Bateson
 */
class ParserUUEncodedPartProxy extends ParserPartProxy
{
    /**
     * @var UUEncodedPartHeaderContainer
     */
    protected $headerContainer;

    public function getNextPartStart()
    {
        return $this->getParent()->getNextPartStart();
    }

    public function getNextPartMode()
    {
        return $this->getParent()->getNextPartMode();
    }

    public function getNextPartFilename()
    {
        return $this->getParent()->getNextPartFilename();
    }

    public function setNextPartStart($nextPartStart)
    {
        $this->getParent()->setNextPartStart($nextPartStart);
    }

    public function setNextPartMode($nextPartMode)
    {
        $this->getParent()->setNextPartMode($nextPartMode);
    }

    public function setNextPartFilename($nextPartFilename)
    {
        $this->getParent()->setNextPartFilename($nextPartFilename);
    }

    public function getUnixFileMode()
    {
        return $this->headerContainer->getUnixFileMode();
    }

    public function getFilename()
    {
        return $this->headerContainer->getFilename();
    }
}
