<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

/**
 * Description of ParserPartProxyDecorator
 *
 * @author Zaahid Bateson
 */
trait PartBuilderDecoratorTrait
{
    public function getParent()
    {
        return $this->partBuilder->getParent();
    }

    public function getHeaderContainer()
    {
        return $this->partBuilder->getHeaderContainer();
    }

     public function getStream()
    {
        return $this->partBuilder->getStream();
    }

    public function getMessageResourceHandle()
    {
        return $this->partBuilder->getMessageResourceHandle();
    }

    public function getMessageResourceHandlePos()
    {
        return $this->partBuilder->getMessageResourceHandlePos();
    }

    public function getStreamPartStartPos()
    {
        return $this->partBuilder->getStreamPartStartPos();
    }

    public function getStreamPartLength()
    {
        return $this->partBuilder->getStreamPartLength();
    }

    public function getStreamContentStartPos()
    {
        return $this->partBuilder->getStreamContentStartPos();
    }

    public function getStreamContentLength()
    {
        return $this->partBuilder->getStreamContentLength();
    }

    public function setStreamPartStartPos($streamPartStartPos)
    {
        $this->partBuilder->setStreamPartStartPos($streamPartStartPos);
    }

    public function setStreamPartEndPos($streamPartEndPos)
    {
        $this->partBuilder->setStreamPartEndPos($streamPartEndPos);
    }

    public function setStreamContentStartPos($streamContentStartPos)
    {
        $this->partBuilder->setStreamContentStartPos($streamContentStartPos);
    }

    public function setStreamPartAndContentEndPos($streamContentEndPos)
    {
        $this->partBuilder->setStreamPartAndContentEndPos($streamContentEndPos);
    }

    public function isContentParsed()
    {
        return $this->partBuilder->isContentParsed();
    }

    public function isMime()
    {
        return $this->partBuilder->isMime();
    }
}
