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
        $ends = (object) ['isSpace' => true, 'canIgnoreSpacesAfter' => true, 'canIgnoreSpacesBefore' => true, 'value' => ''];

        $spaced = \array_merge($parts, [$ends]);
        $filtered = \array_slice(\array_reduce(
            \array_slice(\array_keys($spaced), 0, -1),
            function ($carry, $key) use ($spaced, $ends) {
                $p = $spaced[$key];
                $l = \end($carry);
                $a = $spaced[$key + 1];
                if ($p->isSpace && $a === $ends) {
                    // trim
                    if ($l->isSpace) {
                        \array_pop($carry);
                    }
                    return $carry;
                } elseif ($p->isSpace && ($l->isSpace || ($l->canIgnoreSpacesAfter && $a->canIgnoreSpacesBefore))) {
                    return $carry;
                }
                return \array_merge($carry, [$p]);
            },
            [$ends]
        ), 1);
        return $filtered;
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
