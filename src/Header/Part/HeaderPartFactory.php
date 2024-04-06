<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MailMimeParser\Header\IHeaderPart;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Constructs and returns IHeaderPart objects.
 *
 * @author Zaahid Bateson
 */
class HeaderPartFactory
{
    /**
     * @var MbWrapper $charsetConverter passed to IHeaderPart constructors
     *      for converting strings in IHeaderPart::convertEncoding
     */
    protected MbWrapper $charsetConverter;

    public function __construct(MbWrapper $charsetConverter)
    {
        $this->charsetConverter = $charsetConverter;
    }

    /**
     * Creates and returns a default IHeaderPart for this factory, allowing
     * subclass factories for specialized IHeaderParts.
     *
     * The default implementation returns a new Token
     */
    public function newInstance(string $value) : IHeaderPart
    {
        return $this->newToken($value);
    }

    /**
     * Initializes and returns a new Token.
     */
    public function newToken(string $value, bool $isLiteral = false) : Token
    {
        return new Token($this->charsetConverter, $value, $isLiteral);
    }

    /**
     * Initializes and returns a new Token.
     */
    public function newMimeToken(string $value) : Token
    {
        return new MimeToken($this->charsetConverter, $value);
    }

    /**
     * Initializes and returns a new ContainerPart.
     *
     * @param HeaderPart[] $children
     */
    public function newContainerPart(array $children) : ContainerPart
    {
        return new ContainerPart($this->charsetConverter, $this, $children);
    }

    /**
     * Instantiates and returns a SplitParameterPart.
     *
     * @param ParameterPart[] $children
     */
    public function newSplitParameterPart(array $children) : SplitParameterPart
    {
        return new SplitParameterPart($this->charsetConverter, $this, $children);
    }

    /**
     * Initializes and returns a new QuotedLiteralPart.
     *
     * @param HeaderPart[] $children
     */
    public function newQuotedLiteralPart(array $parts) : QuotedLiteralPart
    {
        return new QuotedLiteralPart($this->charsetConverter, $this, $parts);
    }

    /**
     * Initializes and returns a new CommentPart.
     *
     * @param HeaderPart[] $children
     */
    public function newCommentPart(array $children) : CommentPart
    {
        return new CommentPart($this->charsetConverter, $this, $children);
    }

    /**
     * Initializes and returns a new AddressPart.
     *
     * @param HeaderPart[] $nameParts
     * @param HeaderPart[] $emailParts
     */
    public function newAddress(array $nameParts, array $emailParts) : AddressPart
    {
        return new AddressPart($this->charsetConverter, $this, $nameParts, $emailParts);
    }

    /**
     * Initializes and returns a new AddressGroupPart
     *
     * @param HeaderPart[] $nameParts
     * @param AddressPart[]|AddressGroupPart[] $addressesAndGroups
     */
    public function newAddressGroupPart(array $nameParts, array $addressesAndGroups) : AddressGroupPart
    {
        return new AddressGroupPart($this->charsetConverter, $this, $nameParts, $addressesAndGroups);
    }

    /**
     * Initializes and returns a new DatePart
     *
     * @param HeaderPart[] $children
     */
    public function newDatePart(array $children) : DatePart
    {
        return new DatePart($this->charsetConverter, $this, $children);
    }

    /**
     * Initializes and returns a new ParameterPart.
     *
     * @param HeaderPart[] $nameParts
     */
    public function newParameterPart(array $nameParts, HeaderPart $valuePart) : ParameterPart
    {
        return new ParameterPart($this->charsetConverter, $this, $nameParts, $valuePart);
    }

    /**
     * Initializes and returns a new ReceivedPart.
     *
     * @param HeaderPart[] $children
     */
    public function newReceivedPart(string $name, array $children) : ReceivedPart
    {
        return new ReceivedPart($this->charsetConverter, $this, $name, $children);
    }

    /**
     * Initializes and returns a new ReceivedDomainPart.
     *
     * @param HeaderPart[] $children
     */
    public function newReceivedDomainPart(string $name, array $children) : ReceivedDomainPart
    {
        return new ReceivedDomainPart(
            $this->charsetConverter,
            $this,
            $name,
            $children
        );
    }
}
