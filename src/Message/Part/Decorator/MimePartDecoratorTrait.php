<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part\Decorator;

use ZBateson\MailMimeParser\Message\Part\IMimePart;

/**
 * Description of MimePartDecoratorTrait
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait MimePartDecoratorTrait {

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
        return $this->getPartByContentId($contentId);
    }

    public function isMultiPart()
    {
        return $this->isMultiPart();
    }
}
