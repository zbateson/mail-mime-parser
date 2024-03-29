<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Message\Factory;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Message\PartStreamContainer;
use ZBateson\MailMimeParser\Stream\StreamFactory;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Creates PartStreamContainer instances.
 *
 * @author Zaahid Bateson
 */
class PartStreamContainerFactory
{
    protected StreamFactory $streamFactory;

    protected MbWrapper $mbWrapper;

    protected LoggerInterface $logger;
    
    protected bool $throwExceptionReadingPartContentFromUnsupportedCharsets;

    public function __construct(
        StreamFactory $streamFactory,
        MbWrapper $mbWrapper,
        LoggerInterface $logger,
        bool $throwExceptionReadingPartContentFromUnsupportedCharsets
    ) {
        $this->streamFactory = $streamFactory;
        $this->mbWrapper = $mbWrapper;
        $this->logger = $logger;
        $this->throwExceptionReadingPartContentFromUnsupportedCharsets = $throwExceptionReadingPartContentFromUnsupportedCharsets;
    }

    public function newInstance() : PartStreamContainer
    {
        return new PartStreamContainer(
            $this->streamFactory,
            $this->mbWrapper,
            $this->logger,
            $this->throwExceptionReadingPartContentFromUnsupportedCharsets
        );
    }
}
