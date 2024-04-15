<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory;
use ZBateson\MailMimeParser\Header\Part\ContainerPart;

/**
 * @author Zaahid Bateson
 */
class ParameterNameValueConsumerService extends AbstractGenericConsumerService
{
    public function __construct(
        LoggerInterface $logger,
        MimeTokenPartFactory $partFactory,
        ParameterValueConsumerService $parameterValueConsumerService,
        CommentConsumerService $commentConsumerService,
        QuotedStringConsumerService $quotedStringConsumerService
    ) {
        parent::__construct(
            $logger,
            $partFactory,
            [$parameterValueConsumerService, $commentConsumerService, $quotedStringConsumerService]
        );
    }

    /**
     * Returns semi-colon and equals char as token separators.
     *
     * @return string[]
     */
    protected function getTokenSeparators() : array
    {
        return \array_merge(parent::getTokenSeparators(), [';']);
    }
    
    /**
     * Returns true if the token is an
     */
    protected function isStartToken(string $token) : bool
    {
        return true;
    }

    /**
     * Returns true if the token is a
     */
    protected function isEndToken(string $token) : bool
    {
        return ($token === ';');
    }

    /**
     * Post processing involves creating Part\LiteralPart or Part\ParameterPart
     * objects out of created Token and LiteralParts.
     *
     * @param IHeaderPart[] $parts The parsed parts.
     * @return IHeaderPart[] Array of resulting final parts.
     */
    protected function processParts(array $parts) : array
    {
        $nameOnly = $parts;
        $valuePart = \array_pop($nameOnly);
        if (!($valuePart instanceof ContainerPart)) {
            return [$this->partFactory->newContainerPart($parts)];
        }
        return [$this->partFactory->newParameterPart(
            $nameOnly,
            $valuePart
        )];
    }
}
