<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of MimeLiteralPartFactoryTest
 *
 * @group HeaderParts
 * @group MimeLiteralPartFactory
 * @covers ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory
 * @author Zaahid Bateson
 */
class MimeLiteralPartFactoryTest extends TestCase
{
    protected $headerPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $this->headerPartFactory = new MimeLiteralPartFactory($charsetConverter);
    }

    public function testNewInstance()
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart::class, $token);
    }
}
