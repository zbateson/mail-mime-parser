<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

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
    protected $parserUuEncodedPartFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        ParserUUEncodedPartFactory $f
    ) {
        $this->partBuilderFactory = $pbf;
        $this->parserUuEncodedPartFactory = $f;
    }

    public function newInstance()
    {
        return new NonMimeParser(
            $this->partBuilderFactory,
            $this->parserUuEncodedPartFactory,
            $this
        );
    }

    public function canParse(PartHeaderContainer $messageHeaders)
    {
        return (!$messageHeaders->exists('Content-Type') &&
            !$messageHeaders->exists('Mime-Version'));
    }
}
