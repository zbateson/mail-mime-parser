<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Part\ParsedMessageFactory;

/**
 * Top-level parser for parsing e-mail messages and parts.
 *
 * - holds sub-parsers
 * - proper place to perform any initial setup
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
class BaseParser extends AbstractParser
{
    /**
     * @var ParsedMessageFactory used to create ParsedMessage objects
     */
    protected $parsedMessageFactory;

    public function __construct(
        PartBuilderFactory $pbf,
        ParsedMessageFactory $pmf
    ) {
        parent::__construct($pbf);
        $this->parsedMessageFactory = $pmf;
    }

    protected function parse($handle, PartBuilder $partBuilder)
    {
        $partBuilder->setStreamPartStartPos(ftell($handle));
    }

    public function isSupported(PartBuilder $partBuilder)
    {
        return true;
    }
}
