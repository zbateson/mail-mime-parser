<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Parser;

use ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartFactoryService;
use ZBateson\MailMimeParser\Message\Part\PartBuilder;

/**
 * Description of AbstractParser
 *
 * @author Zaahid Bateson <zaahid.bateson@ubc.ca>
 */
abstract class AbstractParser {

    /**
     * @var PartFactoryService service instance used to create MimePartFactory
     *      objects.
     */
    protected $partFactoryService;

    /**
     * @var PartBuilderFactory used to create PartBuilders
     */
    protected $partBuilderFactory;

    private $subParsers = [];

    private $parent = null;

    /**
     * Sets up the parser with its dependencies.
     *
     * @param PartFactoryService $pfs
     * @param PartBuilderFactory $pbf
     */
    public function __construct(
        PartFactoryService $pfs,
        PartBuilderFactory $pbf
    ) {
        $this->partFactoryService = $pfs;
        $this->partBuilderFactory = $pbf;
    }

    public function getSubParsers()
    {
        return $this->subParsers;
    }

    public function addSubParser(AbstractParser $parser)
    {
        $parser->parent = $this;
        $this->subParsers[] = $parser;
    }

    protected function invokeBaseParser($handle, PartBuilder $partBuilder)
    {
        $top = $this;
        while ($top->parent !== null) {
            $top = $top->parent;
        }
        $top($handle, $partBuilder);
    }

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
     * Reads a line of up to 4096 characters.  If the line is larger than that,
     * the remaining characters in the line are read and discarded, and only the
     * first 4096 characters are returned.
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

    protected abstract function parse($handle, PartBuilder $partBuilder);
    public abstract function isSupported(PartBuilder $partBuilder);
}
