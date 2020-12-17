<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\MessagePartDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use SplSubject;
use SplObserver;

/**
 * Description of ParsedMessagePart
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class ParsedMessagePart implements IMessagePart, SplObserver {

    use MessagePartDecoratorTrait {
        MessagePartDecoratorTrait::getStream as protected getMessagePartStream;
    }

    /**
     *
     * @param IMessagePart $part
     * @param StreamInterface $parsedStream
     */
    public function __construct(IMessagePart $part, StreamInterface $parsedStream)
    {
        $this->part = $part;
        $this->part->attach($this);
        $this->parsedStreams = $parsedStream;
        $this->detachParsedStream = $parsedStream->getMetadata('mmp-detached-stream');
    }

    /**
     * Detaches the parsed stream if
     */
    public function __destruct()
    {
        if ($this->detachParsedStream) {
            $this->parsedStream->detach();
        }
    }

    public function update(SplSubject $subject)
    {
        if ($subject === $this->part) {
            $this->partChanged = true;
        }
    }

    /**
     * Returns a Psr7 StreamInterface containing this part, including any
     * headers for a MimePart, its content, and all its children.
     *
     * @return StreamInterface the resource handle
     */
    public function getStream()
    {
        if (!$this->partChanged) {
            return $this->parsedStreams->getStream();
        }
        return $this->getMessagePartStream();
    }
}
