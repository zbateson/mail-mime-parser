<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

/**
 * Base class for parsers to extend, handles parsing a resource input stream
 * into a {@see PartBuilder}.
 *
 * Each parser defines a set of 'sub' parsers that are invoked by the default
 * implementation of {@see AbstractParser::__invoke()} after it calls
 * {@see AbstractParser::parse()} on the current AbstractParser object.  The
 * hierarchy therefore is not one of 'superclass/subclass', but instead is
 * configured by calling {@see AbstractParser::addSubParser()}.
 *
 * Generally subclasses of AbstractParser shouldn't need to override invoke,
 * and instead should override {@see AbstractParser::parse()} and
 * {@see AbstractParser::isSupported()} only, unless some special handling of
 * sub parser invocation is needed (or the order of invocation, etc...).
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
abstract class AbstractParser
{
    /**
     * @var PartBuilderFactory used to create PartBuilders
     */
    protected $partBuilderFactory;

    /**
     * @var AbstractParser[] sub parsers
     */
    private $subParsers = [];

    /**
     * @var AbstractParser parent parser
     */
    private $parent = null;

    public function __construct(
        PartBuilderFactory $pbf
    ) {
        $this->partBuilderFactory = $pbf;
    }

    /**
     * Returns the array of sub parsers.
     *
     * @return AbstractParser[]
     */
    public function getSubParsers()
    {
        return $this->subParsers;
    }

    /**
     * Adds the passed $parser as a sub parser.
     *
     * @param AbstractParser $parser
     */
    public function addSubParser(AbstractParser $parser)
    {
        $parser->parent = $this;
        $this->subParsers[] = $parser;
    }

    /**
     * Callable from any level to invoke the top-level parser in the chain.
     *
     * @param resource $handle the input stream handle to parser
     * @param PartBuilder $partBuilder
     */
    protected function invokeBaseParser($handle, PartBuilder $partBuilder)
    {
        $top = $this;
        while ($top->parent !== null) {
            $top = $top->parent;
        }
        $top($handle, $partBuilder);
    }

    /**
     * Calls {@see AbstractParser::parse()} on the current parser, then iterates
     * over sub parsers, checking if the sub-parser supports the state
     * $partBuilder is currently in, and calling '__invoke' on each one in order
     * if {@see AbstractParser::isSupported()} returns true.
     *
     * @param resource $handle
     * @param PartBuilder $partBuilder
     */
    public function __invoke($handle, PartBuilder $partBuilder)
    {
        $this->parse($handle, $partBuilder);
        foreach ($this->subParsers as $p) {
            if ($p->isSupported($partBuilder)) {
                $p($handle, $partBuilder);
                return;
            }
        }
    }

    /**
     * Convenience method to read a line of up to 4096 characters from the
     * passed resource handle.
     *
     * If the line is larger than 4096 characters, the remaining characters in
     * the line are read and discarded, and only the first 4096 characters are
     * returned.
     *
     * @param resource $handle
     * @return string
     */
    protected function readLine($handle)
    {
        $size = 4096;
        $ret = $line = fgets($handle, $size);
        while (strlen($line) === $size - 1 && substr($line, -1) !== "\n") {
            $line = fgets($handle, $size);
        }
        return $ret;
    }

    /**
     * Parses the passed resource handle into the passed $partBuilder.
     *
     * @param resource $handle
     * @param PartBuilder $partBuilder
     */
    protected abstract function parse($handle, PartBuilder $partBuilder);

    /**
     * Returns true if the passed $partBuilder is in a state currently
     * supported for parsing by this parser.
     *
     * @param PartBuilder $partBuilder
     * @return boolean
     */
    protected abstract function isSupported(PartBuilder $partBuilder);
}
