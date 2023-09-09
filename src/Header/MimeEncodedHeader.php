<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\AbstractConsumerService;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPart;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Allows a header to be mime-encoded and be decoded with a consumer after
 * decoding.
 *
 * @author Zaahid Bateson
 */
abstract class MimeEncodedHeader extends AbstractHeader
{
    /**
     * @var MimeLiteralPartFactory for mime decoding.
     */
    protected $mimeLiteralPartFactory;

    public function __construct(
        MimeLiteralPartFactory $mimeLiteralPartFactory,
        ConsumerService $consumerService,
        $name,
        $value
    ) {
        $this->mimeLiteralPartFactory = $mimeLiteralPartFactory;
        parent::__construct($consumerService, $name, $value);
    }

    /**
     * Mime-decodes any mime-encoded parts prior to invoking
     * parent::parseHeaderValue.
     */
    protected function parseHeaderValue(AbstractConsumerService $consumer, string $value) : AbstractHeader
    {
        $matchp = '~' . MimeLiteralPart::MIME_PART_PATTERN . '~';
        $rep = \preg_replace_callback($matchp, function($matches) {
            return $this->mimeLiteralPartFactory->newInstance($matches[0])->getValue();
        }, $value);
        parent::parseHeaderValue($consumer, $rep);
        return $this;
    }
}
