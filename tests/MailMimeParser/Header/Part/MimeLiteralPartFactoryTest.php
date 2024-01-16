<?php

namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\TestCase;

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
    // @phpstan-ignore-next-line
    protected $headerPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $this->headerPartFactory = new MimeLiteralPartFactory($charsetConverter);
    }

    public function testNewInstance() : void
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf('\\' . \ZBateson\MailMimeParser\Header\Part\MimeLiteralPart::class, $token);
    }
}
