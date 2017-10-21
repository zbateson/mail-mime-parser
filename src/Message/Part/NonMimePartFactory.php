<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Description of NonMimePartFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class NonMimePartFactory extends MimePartFactory
{
    /**
     * Constructs a new NonMimePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Message\NonMimePart
     */
    public function newInstance()
    {
        return new NonMimePart(
            $this->headerFactory,
            $this->messageWriterService->getMimePartWriter()
        );
    }
}
