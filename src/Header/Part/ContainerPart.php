<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use Psr\Log\LoggerInterface;
use ZBateson\MailMimeParser\ErrorBag;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Base HeaderPart for a part that consists of other parts.
 *
 * The base container part constructs a string value out of the passed parts by
 * concatenating their values, discarding whitespace between parts that can be
 * ignored (in general allows for a single space but removes extras.)
 *
 * A ContainerPart can also contain any number of child comment parts.  The
 * CommentParts in this and all child parts can be returned by calling
 * getComments.
 *
 * @author Zaahid Bateson
 */
class ContainerPart extends HeaderPart
{
    /**
     * @var HeaderPart[] parts that were used to create this part, collected for
     *      proper error reporting and validation.
     */
    protected $children = [];

    public function __construct(
        LoggerInterface $logger,
        MbWrapper $charsetConverter,
        array $children
    ) {
        ErrorBag::__construct($logger);
        $this->charsetConverter = $charsetConverter;
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
        $filtered = [];
        $count = \count($parts);
        for ($key = 0; $key < $count; ++$key) {
            $p = $parts[$key];
            if ($p->isSpace) {
                // null when nothing has been kept yet, i.e. a leading space
                $l = empty($filtered) ? null : $filtered[\count($filtered) - 1];
                // null when $p is the last part, i.e. a trailing space
                $a = $parts[$key + 1] ?? null;
                if ($a === null) {
                    if ($l !== null && $l->isSpace) {
                        \array_pop($filtered);
                    }
                    continue;
                }
                if ($l === null || $l->isSpace
                    || ($l->canIgnoreSpacesAfter && $a->canIgnoreSpacesBefore)) {
                    continue;
                }
            }
            $filtered[] = $p;
        }
        return $filtered;
    }

    /**
     * Creates the string value representation of this part constructed from the
     * child parts passed to it.
     *
     * The default implementation filters out ignorable whitespace between
     * parts, and concatenates parts calling 'getValue'.
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

    public function getComments() : array
    {
        return \array_merge(...\array_filter(\array_map(
            fn ($p) => ($p instanceof CommentPart) ? [$p] : $p->getComments(),
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
