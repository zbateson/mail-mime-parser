<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
use Psr\Log\LogLevel;

/**
 * Represents a name/value pair part of a header.
 *
 * @author Zaahid Bateson
 */
class NameValuePart extends ContainerPart
{
    /**
     * @var string the name of the part
     */
    protected string $name;

    public function __construct(
        MbWrapper $charsetConverter,
        HeaderPartFactory $headerPartFactory,
        array $nameParts,
        array $valueParts
    ) {
        $this->charsetConverter = $charsetConverter;
        $this->partFactory = $headerPartFactory;
        $this->name = (!empty($nameParts)) ? $this->getNameFromParts($nameParts) : '';
        parent::__construct($charsetConverter, $headerPartFactory, $valueParts);
        \array_unshift($this->children, ...$nameParts);
    }

    /**
     * Creates the string 'name' representation of this part constructed from
     * the child name parts passed to it.
     *
     * @param HeaderParts[] $parts
     */
    protected function getNameFromParts(array $parts) : string
    {
        return \array_reduce($this->filterIgnoredSpaces($parts), fn ($c, $p) => $c . $p->getValue(), '');
    }

    /**
     * Returns the name of the name/value part.
     */
    public function getName() : string
    {
        return $this->name;
    }

    protected function validate() : void
    {
        if ($this->value === '') {
            $this->addError('NameValuePart value is empty', LogLevel::NOTICE);
        }
    }
}
