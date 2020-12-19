<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use ZBateson\MailMimeParser\Stream\StreamFactory;

/**
 * Description of ParsedPartStreamContainerFactory
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
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

    public function newInstance() {
        return new ParsedPartStreamContainer($this->streamFactory);
    }
}
