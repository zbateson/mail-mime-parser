<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Parser\Proxy\ParserUUEncodedPartFactory;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Description of NonMimeParserFactory
 *
 * @author Zaahid Bateson
 */
class NonMimeParserFactory implements IParserFactory
{
    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var ParserUUEncodedPartFactory for ParsedMimePart objects
     */
    protected $parserUUEncodedPartFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        ParserUUEncodedPartFactory $f
    ) {
        $this->partBuilderFactory = $pbf;
        $this->parserUUEncodedPartFactory = $f;
    }

    public function newInstance()
    {
        return new NonMimeParser(
            $this->partBuilderFactory,
            $this->parserUUEncodedPartFactory,
            $this
        );
    }

    public function canParse(PartHeaderContainer $messageHeaders)
    {
        return (!$messageHeaders->exists(HeaderConsts::CONTENT_TYPE) &&
            !$messageHeaders->exists(HeaderConsts::MIME_VERSION));
    }
}
