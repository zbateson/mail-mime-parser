<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\IUUEncodedPart;

/**
 * Ferries calls to an IUUEncodedPart.
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
trait UUEncodedPartDecoratorTrait
{
    use MessagePartDecoratorTrait;

    /**
     * @var IUUEncodedPart The underlying part to wrap.
     */
    protected $part;

    public function __construct(IUUEncodedPart $part)
    {
        $this->part = $part;
    }

    public function getUnixFileMode()
    {
        return $this->part->getUnixFileMode();
    }

    public function setFilename($filename)
    {
        $this->part->setFilename($filename);
    }

    public function setUnixFileMode($mode)
    {
        $this->part->setUnixFileMode($mode);
    }
}
