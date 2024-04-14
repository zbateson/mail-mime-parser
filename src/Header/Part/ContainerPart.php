<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use Psr\Log\LoggerInterface;
use ZBateson\MbWrapper\MbWrapper;
use ZBateson\MailMimeParser\ErrorBag;

/**
 * Base HeaderPart for a part that consists of other parts.
 *
 * @author Zaahid Bateson
 */
class ContainerPart extends HeaderPart
{
    /**
     * @var HeaderPartFactory used to create intermediate parts.
     */
    protected HeaderPartFactory $partFactory;

    /**
     * @var HeaderPart[] parts that were used to create this part, collected for
     *      proper error reporting and validation.
     */
    protected $children = [];

    public function __construct(
        LoggerInterface $logger,
        MbWrapper $charsetConverter,
        HeaderPartFactory $headerPartFactory,
        array $children
    ) {
        ErrorBag::__construct($logger);
        $this->charsetConverter = $charsetConverter;
        $this->partFactory = $headerPartFactory;
        $this->children = $children;
        $str = (!empty($children)) ? $this->getValueFromParts($children) : '';
        parent::__construct(
            $logger,
            $this->charsetConverter,
            $str
        );
    }

    /**
     * Filters out ignorable space tokens.
     *
     * Spaces are removed if parts on either side of it have their
     * canIgnoreSpaceAfter/canIgnoreSpaceBefore properties set to true.
     *
     * @param HeaderPart[] $parts
     * @return HeaderPart[]
     */
    protected function filterIgnoredSpaces(array $parts) : array
    {
        $space = $this->partFactory->newToken(' ');
        // creates an array of 3 parts, the first consisting of $parts shifted
        // one to the right starting with a space, the second $parts itself,
        // the 3rd $parts shifted one to the left and with a space after
        $zipped = \array_map(
            null,
            \array_slice(\array_merge([$space], $parts), 0, -1),
            $parts,
            \array_merge(\array_slice($parts, 1), [$space])
        );
        // reassembles $parts using the $zipped array, the \array_filter callback
        // gets an array of 'before'/'current'/'after' elements
        $filtered = \array_map(fn ($arr) => $arr[1], \array_filter($zipped, function ($arr) {
            return (!$arr[1]->isSpace || !$arr[0]->canIgnoreSpacesAfter || !$arr[2]->canIgnoreSpacesBefore);
        }));
        return $filtered;
    }

    /**
     * Trims any 'space' tokens from the beginning and end of an array of parts.
     *
     * @param HeaderPart[] $parts
     * @return HeaderPart[]
     */
    protected function trim(array $parts): array
    {
        $dost = true;
        $doet = true;
        do {

            $st = ($dost) ? \array_shift($parts) : null;
            $et = ($doet) ? \array_pop($parts) : null;
            if ($st !== null && !$st->isSpace) {
                \array_unshift($parts, $st);
                $st = null;
            }
            if ($et !== null && !$et->isSpace) {
                \array_push($parts, $et);
                $et = null;
            }
            $dost = ($st !== null);
            $doet = ($et !== null);

        } while ($dost || $doet);
        return $parts;
    }

    /**
     * Creates the string value representation of this part constructed from the
     * child parts passed to it.
     *
     * @param HeaderParts[] $parts
     */
    protected function getValueFromParts(array $parts) : string
    {
        return \array_reduce($this->filterIgnoredSpaces($parts), fn ($c, $p) => $c . $p->getValue(), '');
    }

    /**
     * Returns the child parts this container part consists of.
     *
     * @return IHeaderPart[]
     */
    public function getChildParts() : array
    {
        return $this->children;
    }

    public function getCommentParts() : array
    {
        return \array_merge(...\array_filter(\array_map(
            fn ($p) => ($p instanceof CommentPart) ? [$p] : $p->getCommentParts(),
            $this->children
        )));
    }

    /**
     * Returns this part's children, same as getChildParts().
     *
     * @return ErrorBag
     */
    protected function getErrorBagChildren() : array
    {
        return $this->children;
    }
}
