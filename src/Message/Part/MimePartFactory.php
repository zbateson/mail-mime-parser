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
    protected function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }
    
    /**
     * Returns the singleton instance for the class.
     * 
     * @param HeaderFactory $hf
     * @return MimePartFactory
     */
    public static function getInstance(HeaderFactory $hf = null)
    {
        static $instances = [];
        $class = get_called_class();
        if (!isset($instances[$class])) {
            $instances[$class] = new static($hf);
        }
        return $instances[$class];
    }

    /**
     * Constructs a new MimePart object and returns it
     * 
     * @param resource $handle
     * @param resource $contentHandle
     * @param ZBateson\MailMimeParser\Message\Part\MessagePart[] $children
     * @param array $headers
     * @param array $properties
     * @return \ZBateson\MailMimeParser\Message\Part\MimePart
     */
    public function newInstance(
        $handle,
        $contentHandle,
        array $children,
        array $headers,
        array $properties
    ) {
        return new MimePart(
            $this->headerFactory,
            $handle,
            $contentHandle,
            $children,
            $headers
        );
    }
}
