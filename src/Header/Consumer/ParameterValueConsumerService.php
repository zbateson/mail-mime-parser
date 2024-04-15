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

/**
 * @author Zaahid Bateson
 */
class ParameterValueConsumerService extends GenericConsumerMimeLiteralPartService
{
    public function __construct(
        LoggerInterface $logger,
        MimeTokenPartFactory $partFactory,
        CommentConsumerService $commentConsumerService,
        QuotedStringMimeLiteralPartConsumerService $quotedStringConsumerService
    ) {
        parent::__construct(
            $logger,
            $partFactory,
            $commentConsumerService,
            $quotedStringConsumerService
        );
    }

    /**
     * Returns semi-colon and equals char as token separators.
     *
     * @return string[]
     */
    protected function getTokenSeparators() : array
    {
        return \array_merge(parent::getTokenSeparators(), ['=']);
    }
    
    /**
     * Returns true if the token is an '=' character.
     */
    protected function isStartToken(string $token) : bool
    {
        return ($token === '=');
    }

    /**
     * Returns true if the token is a ';' character.
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
        return [$this->partFactory->newContainerPart($parts)];
    }
}
