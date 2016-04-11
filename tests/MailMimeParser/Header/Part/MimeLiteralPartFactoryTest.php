<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of MimeLiteralPartFactoryTest
 *
 * @group HeaderParts
 * @group MimeLiteralPartFactory
 * @covers ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory
 * @author Zaahid Bateson
 */
class MimeLiteralPartFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $headerPartFactory;
    
    protected function setUp()
    {
        $this->headerPartFactory = new MimeLiteralPartFactory();
    }
    
    public function testNewInstance()
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\ZBateson\MailMimeParser\Header\Part\MimeLiteralPart', $token);
    }
}
