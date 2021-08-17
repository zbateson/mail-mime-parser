<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser;

use ZBateson\MailMimeParser\Parser\Proxy\ParserPartProxyFactory;

/**
 * Description of AbstractParser
 *
 * @author Zaahid Bateson
 */
abstract class AbstractParser implements IParser
{
    protected $parserMessageProxyFactory;

    protected $parserPartProxyFactory;

    /**
     * @var ParserManager
     */
    protected $parserManager;

    public function __construct(
        ParserPartProxyFactory $parserMessageProxyFactory,
        ParserPartProxyFactory $parserPartProxyFactory
    ) {
        $this->parserMessageProxyFactory = $parserMessageProxyFactory;
        $this->parserPartProxyFactory = $parserPartProxyFactory;
    }

    public function setParserManager(ParserManager $pm)
    {
        $this->parserManager = $pm;
    }

    public function getParserMessageProxyFactory()
    {
        return $this->parserMessageProxyFactory;
    }

    public function getParserPartProxyFactory()
    {
        return $this->parserPartProxyFactory;
    }
}
