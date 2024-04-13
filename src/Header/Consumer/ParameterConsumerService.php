<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Consumer;

use Iterator;
use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\ParameterPart;

/**
 * Reads headers separated into parameters consisting of an optional main value,
 * and subsequent name/value pairs - for example text/html; charset=utf-8.
 *
 * A ParameterConsumerService's parts are separated by a semi-colon.  Its
 * name/value pairs are separated with an '=' character.
 *
 * Parts may be mime-encoded entities.  Additionally, a value can be quoted and
 * comments may exist.
 *
 * @author Zaahid Bateson
 */
class ParameterConsumerService extends AbstractGenericConsumerService
{
    public function __construct(
        LoggerInterface $logger,
        HeaderPartFactory $partFactory,
        ParameterNameValueConsumerService $parameterNameValueConsumerService,
        CommentConsumerService $commentConsumerService,
        QuotedStringConsumerService $quotedStringConsumerService
    ) {
        parent::__construct(
            $logger,
            $partFactory,
            [$parameterNameValueConsumerService, $commentConsumerService, $quotedStringConsumerService]
        );
    }

    /**
     * Disables advancing for start tokens.
     *
     * @return static
     */
    protected function advanceToNextToken(Iterator $tokens, bool $isStartToken) : static
    {
        if ($isStartToken) {
            return $this;
        }
        parent::advanceToNextToken($tokens, $isStartToken);
        return $this;
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
        $factory = $this->partFactory;
        return \array_values(\array_map(
            function ($partsArray) use ($factory) {
                if (count($partsArray) > 1) {
                    return $factory->newSplitParameterPart($partsArray);
                }
                return $partsArray[0];
            },
            \array_merge_recursive(...\array_map(
                function ($p) {
                    if ($p instanceOf ParameterPart && $p->getIndex() !== null) {
                        return [strtolower($p->getName()) => [$p]];
                    } else {
                        return [';' . spl_object_id($p) . ';' => [$p]];
                    }
                },
                $parts
            ))
        ));
    }
}
