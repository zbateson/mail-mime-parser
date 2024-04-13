<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use Psr\Log\LoggerInterface;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Holds a running value for an RFC-2231 split header parameter.
 *
 * ParameterConsumer creates SplitParameterTokens when a split header parameter
 * is first found, and adds subsequent split parts to an already created one if
 * the parameter name matches.
 *
 * @author Zaahid Bateson
 */
class SplitParameterPart extends ParameterPart
{
    /**
     * Initializes a SplitParameterToken.
     *
     * @param ParameterPart[] $children
     */
    public function __construct(
        LoggerInterface $logger,
        MbWrapper $charsetConverter,
        HeaderPartFactory $headerPartFactory,
        array $children
    ) {
        parent::__construct($logger, $charsetConverter, $headerPartFactory, [$children[0]], $children[0]);
        $this->children = $children;
        $this->value = $this->getValueFromParts($children);
    }

    protected function getNameFromParts(array $parts) : string
    {
        return $parts[0]->getName();
    }

    protected function getValueFromParts(array $parts) : string
    {
        $sorted = $parts;
        \usort($sorted, fn ($a, $b) => $a->getIndex() <=> $b->getIndex());
        $first = \array_shift($sorted);
        $this->language = $first->language;
        $charset = $this->charset = $first->charset;

        // intval to match ParameterPart's check, so a null would match on 0
        if (intval($first->index) !== 0) {
            // wouldn't have been decoded.
            \array_unshift($sorted, $first);
        }

        return $first->getValue() . implode(\array_map(
            fn ($p) => ($p->encoded) ? $this->decodePartValue($p->value, ($p->charset === null) ? $charset : $p->charset) : $p->value,
            $sorted
        ));
    }
}
