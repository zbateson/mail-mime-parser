<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

/**
 * Description of ParsedMessagePartTrait
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait ParsedMessagePartTrait {

    /**
     * @var bool set to true if the part's been changed, and the attached stream
     *      in $partStreamContainer would no longer represent the actual content
     *      of the part, and a MessagePartStream should be used instead.
     */
    protected $partChanged = false;

    /**
     * @var bool true if the stream should be detached when this part is
     *      destroyed.
     */
    protected $detachParsedStream;

    /**
     * @var StreamInterface containing the original parsed stream, kept
     *      separately so it can be detached if need be, and so a reference is
     *      maintained while the Message lives.
     */
    protected $parsedStream;

    /**
     * Detaches the parsed stream if
     */
    public function __destruct()
    {
        if ($this->detachParsedStream) {
            $this->parsedStream->detach();
        }
    }
}
