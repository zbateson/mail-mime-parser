<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\IMimePart;

/**
 * Ferries calls to an IMimePart.
 *
 * @author Zaahid Bateson
 */
trait MimePartDecoratorTrait
{
    use ParentHeaderPartDecoratorTrait;

    /**
     * @var IMimePart The underlying part to wrap.
     */
    protected $part;

    public function __construct(IMimePart $part)
    {
        $this->part = $part;
    }

    public function getPartByContentId($contentId)
    {
        return $this->part->getPartByContentId($contentId);
    }

    public function isMultiPart()
    {
        return $this->part->isMultiPart();
    }
}
