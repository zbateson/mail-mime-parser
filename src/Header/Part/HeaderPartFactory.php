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

    /**
     * Sets up dependencies.
     *
     */
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
    public function newToken(string $value) : Token
    {
        return new Token($this->charsetConverter, $value);
    }

    /**
     * Instantiates and returns a SplitParameterToken with the given name.
     */
    public function newSplitParameterToken(string $name) : SplitParameterToken
    {
        return new SplitParameterToken($this->charsetConverter, $name);
    }

    /**
     * Initializes and returns a new LiteralPart.
     */
    public function newLiteralPart(string $value) : LiteralPart
    {
        return new LiteralPart($this->charsetConverter, $value);
    }

    /**
     * Initializes and returns a new MimeLiteralPart.
     */
    public function newMimeLiteralPart(string $value) : MimeLiteralPart
    {
        return new MimeLiteralPart($this->charsetConverter, $value);
    }

    /**
     * Initializes and returns a new CommentPart.
     */
    public function newCommentPart(string $value) : CommentPart
    {
        return new CommentPart($this->charsetConverter, $value);
    }

    /**
     * Initializes and returns a new AddressPart.
     */
    public function newAddressPart(string $name, string $email) : AddressPart
    {
        return new AddressPart($this->charsetConverter, $name, $email);
    }

    /**
     * Initializes and returns a new AddressGroupPart
     *
     * @param AddressPart[] $addresses
     */
    public function newAddressGroupPart(array $addresses, string $name = '') : AddressGroupPart
    {
        return new AddressGroupPart($this->charsetConverter, $addresses, $name);
    }

    /**
     * Initializes and returns a new DatePart
     */
    public function newDatePart(string $value) : DatePart
    {
        return new DatePart($this->charsetConverter, $value);
    }

    /**
     * Initializes and returns a new ParameterPart.
     */
    public function newParameterPart(string $name, string $value, ?string $language = null) : ParameterPart
    {
        return new ParameterPart($this->charsetConverter, $name, $value, $language);
    }

    /**
     * Initializes and returns a new ReceivedPart.
     */
    public function newReceivedPart(string $name, string $value) : ReceivedPart
    {
        return new ReceivedPart($this->charsetConverter, $name, $value);
    }

    /**
     * Initializes and returns a new ReceivedDomainPart.
     */
    public function newReceivedDomainPart(
        string $name,
        ?string $value = null,
        ?string $ehloName = null,
        ?string $hostName = null,
        ?string $hostAddress = null
    ) : ReceivedDomainPart {
        return new ReceivedDomainPart(
            $this->charsetConverter,
            $name,
            $value,
            $ehloName,
            $hostName,
            $hostAddress
        );
    }
}
