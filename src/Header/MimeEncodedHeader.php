<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ZBateson\MailMimeParser\Header\Consumer\IConsumerService;
use ZBateson\MailMimeParser\Header\Part\MimeToken;
use ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory;

/**
 * Allows a header to be mime-encoded and be decoded with a consumer after
 * decoding.
 *
 * @author Zaahid Bateson
 */
abstract class MimeEncodedHeader extends AbstractHeader
{
    /**
     * @var IHeaderPart[] the mime encoded parsed parts of this header that
     *      recorded errors while decoding
     */
    protected array $mimeEncodedParsedParts = [];

    public function __construct(
        LoggerInterface $logger,
        protected readonly MimeTokenPartFactory $mimeTokenPartFactory,
        IConsumerService $consumerService,
        string $name,
        string $value,
        ?int $maxTokenCount = null
    ) {
        parent::__construct($logger, $consumerService, $name, $value, $maxTokenCount);
    }

    /**
     * Mime-decodes any mime-encoded parts prior to invoking
     * parent::parseHeaderValue.
     */
    protected function parseHeaderValue(IConsumerService $consumer, string $value, ?int $maxTokenCount = null) : void
    {
        // handled differently from MimeLiteralPart's decoding which ignores
        // whitespace between parts, etc...
        $matchp = '~(' . MimeToken::MIME_PART_PATTERN . ')~';
        $aMimeParts = \preg_split($matchp, $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $parts = \array_map($this->mimeTokenPartFactory->newInstance(...), $aMimeParts);
        parent::parseHeaderValue(
            $consumer,
            \implode('', \array_map(fn ($part) => $part->getValue(), $parts)),
            $maxTokenCount
        );
        // only kept so their errors are reachable, so parts without any can go
        $this->mimeEncodedParsedParts = \array_values(\array_filter(
            $parts,
            fn ($part) => $part->hasAnyErrors(false, LogLevel::DEBUG)
        ));
    }

    protected function getErrorBagChildren() : array
    {
        return \array_values(\array_merge($this->getAllParts(), $this->mimeEncodedParsedParts));
    }
}
