<?php
namespace ZBateson\MailMimeParser;

use PHPUnit\Framework\TestCase;

/**
 * Description of ContainerTest
 *
 * @group Container
 * @group Base
 * @covers ZBateson\MailMimeParser\Container
 * @author Zaahid Bateson
 */
class ContainerTest extends TestCase
{
    private $di;
    
    protected function setUp()
    {
        $this->di = new Container();
    }
    
    public function testNewMessageParser()
    {
        $mp = $this->di->newMessageParser();
        $this->assertNotNull($mp);
    }

    public function testGetCharsetConverter()
    {
        $m = $this->di->getCharsetConverter('ISO-8859-1', 'UTF-8');
        $this->assertNotNull($m);
    }

    public function testGetHeaderFactory()
    {
        $singleton = $this->di->getHeaderFactory();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $this->di->getHeaderFactory());
    }

    public function testGetHeaderPartFactory()
    {
        $singleton = $this->di->getHeaderPartFactory();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $this->di->getHeaderPartFactory());
    }

    public function testGetMimeLiteralPartFactory()
    {
        $singleton = $this->di->getMimeLiteralPartFactory();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $this->di->getMimeLiteralPartFactory());
    }

    public function testGetConsumerService()
    {
        $singleton = $this->di->getConsumerService();
        $this->assertNotNull($singleton);
        $this->assertSame($singleton, $this->di->getConsumerService());
    }
}
