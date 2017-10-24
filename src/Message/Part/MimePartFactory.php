<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Responsible for creating MimePart instances.
 *
 * @author Zaahid Bateson
 */
class MimePartFactory extends MessagePartFactory
{
    /**
     * @var \ZBateson\MailMimeParser\Header\HeaderFactory the HeaderFactory
     *      instance
     */
    protected $headerFactory;

    /**
     * Creates a MimePartFactory instance with its dependencies.
     * 
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }

    /**
     * Constructs a new MimePart object and returns it
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(
        $handle,
        $contentHandle,
        MimePart $parent,
        array $children,
        array $headers,
        array $properties
    ) {
        return new MimePart(
            $this->headerFactory,
            $handle,
            $contentHandle,
            $parent,
            $children,
            $headers
        );
    }
}
