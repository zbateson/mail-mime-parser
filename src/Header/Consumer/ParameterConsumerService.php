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
 * Parts may be mime-encoded entities, or RFC-2231 split/encoded parts.
 * Additionally, a value can be quoted and comments may exist.
 *
 * Actual processing of parameters is done in ParameterNameValueConsumerService,
 * with ParameterConsumerService processing all collected parts into split
 * parameter parts as necessary.
 *
 * @author Zaahid Bateson
 */
class ParameterConsumerService extends AbstractGenericConsumerService
{
    use QuotedStringMimeLiteralPartTokenSplitPatternTrait;

    public function __construct(
        LoggerInterface $logger,
        HeaderPartFactory $partFactory,
        ParameterNameValueConsumerService $parameterNameValueConsumerService,
        CommentConsumerService $commentConsumerService,
        QuotedStringConsumerService $quotedStringConsumerService,
        int $maxHeaderTokenCount = 20000
    ) {
        parent::__construct(
            $logger,
            $partFactory,
            [$parameterNameValueConsumerService, $commentConsumerService, $quotedStringConsumerService],
            $maxHeaderTokenCount
        );
    }

    /**
     * Disables advancing for start tokens.
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
     * Post processing involves looking for split parameter parts with matching
     * names and combining them into a SplitParameterPart, and otherwise
     * returning ParameterParts from ParameterNameValueConsumer as-is.
     *
     * @param IHeaderPart[] $parts The parsed parts.
     * @return IHeaderPart[] Array of resulting final parts.
     */
    protected function processParts(array $parts) : array
    {
        $factory = $this->partFactory;
        // Parts are grouped by key so split parameters end up together: if
        // $p->getIndex() is non-null it's a split-parameter part and groups
        // under its lower-cased name, otherwise it gets a key unique to itself
        // so it stays on its own.
        $split = [];
        $result = [];
        foreach ($parts as $p) {
            if ($p instanceof ParameterPart && $p->getIndex() !== null) {
                $name = \strtolower($p->getName());
                // reserve the position so the original ordering is kept, the
                // combined part is set below once all its pieces are known
                $result[$name] = $p;
                $split[$name][] = $p;
            } else {
                $result[';' . \spl_object_id($p) . ';'] = $p;
            }
        }
        foreach ($split as $name => $partsArray) {
            if (\count($partsArray) > 1) {
                $result[$name] = $factory->newSplitParameterPart($partsArray);
            }
        }
        return \array_values($result);
    }
}
