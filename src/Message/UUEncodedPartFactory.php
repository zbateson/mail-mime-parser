<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

/**
 * Description of UUEncodedPartFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class UUEncodedPartFactory extends MimePartFactory
{
    /**
     * @var int the file mode
     */
    protected $mode = 0600;
    
    /**
     * @var string the filename
     */
    protected $filename;
    
    /**
     * Constructs a new UUEncodedPart object and returns it
     * 
     * @return 
     */
    public function newInstance(
        $handle,
        MimePart $parent,
        array $children,
        array $headers,
        array $properties
    ) {
        return new UUEncodedPart(
            $this->headerFactory,
            $this->messageWriterService->getMimePartWriter(),
            $handle,
            $parent,
            $children,
            $headers,
            $properties
        );
    }
}
