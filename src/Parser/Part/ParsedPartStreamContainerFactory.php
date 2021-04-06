<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Parser\PartBuilder;
use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Description of ParsedPartStreamContainerFactory
 *
 * @author Zaahid Bateson
 */
class ParsedPartStreamContainerFactory
{
    /**
     * @var StreamFactory
     */
    protected $streamFactory;

    public function __construct(StreamFactory $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    public function newInstance(PartBuilder $builder) {
        return new ParsedPartStreamContainer($this->streamFactory, $builder);
    }
}
