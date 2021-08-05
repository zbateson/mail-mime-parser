<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Message\PartHeaderContainer;
use ZBateson\MailMimeParser\Message\Factory\PartHeaderContainerFactory;
use ZBateson\MailMimeParser\Parser\Proxy\ParserMimePartFactory;

/**
 * Description of MimeParserFactory
 *
 * @author Zaahid Bateson
 */
class MimeParserFactory implements IParserFactory
{
    /**
     * @var PartHeaderContainerFactory
     */
    protected $partHeaderContainerFactory;

    /**
     * @var PartBuilderFactory
     */
    protected $partBuilderFactory;

    /**
     * @var HeaderParser
     */
    protected $headerParser;

    /**
     * @var ParserMimePartFactory for ParserMimePart objects
     */
    protected $parserMimePartFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        PartHeaderContainerFactory $phcf,
        HeaderParser $hp,
        ParserMimePartFactory $f
    ) {
        $this->partBuilderFactory = $pbf;
        $this->partHeaderContainerFactory = $phcf;
        $this->headerParser = $hp;
        $this->parserMimePartFactory = $f;
    }

    public function newInstance()
    {
        return new MimeParser(
            $this->partBuilderFactory,
            $this->partHeaderContainerFactory,
            $this->headerParser,
            $this->parserMimePartFactory,
            $this
        );
    }

    public function canParse(PartHeaderContainer $messageHeaders)
    {
        return ($messageHeaders->exists('Content-Type') ||
            $messageHeaders->exists('Mime-Version'));
    }
}
