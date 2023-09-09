<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message\Helper;

use ZBateson\MailMimeParser\Container\IService;
use ZBateson\MailMimeParser\Message\Factory\IMimePartFactory;
use ZBateson\MailMimeParser\Message\Factory\IUUEncodedPartFactory;

/**
 * Base class for message helpers.
 *
 * @author Zaahid Bateson
 */
abstract class AbstractHelper implements IService
{
    /**
     * @var IMimePartFactory to create parts for attachments/content
     */
    protected $mimePartFactory;

    /**
     * @var IUUEncodedPartFactory to create parts for attachments
     */
    protected $uuEncodedPartFactory;

    public function __construct(
        IMimePartFactory $mimePartFactory,
        IUUEncodedPartFactory $uuEncodedPartFactory
    ) {
        $this->mimePartFactory = $mimePartFactory;
        $this->uuEncodedPartFactory = $uuEncodedPartFactory;
    }
}
