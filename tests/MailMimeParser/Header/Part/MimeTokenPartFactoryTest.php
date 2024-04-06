<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of MimeTokenPartFactoryTest
 *
 * @group HeaderParts
 * @group MimeTokenPartFactory
 * @covers ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory
 * @author Zaahid Bateson
 */
class MimeTokenPartFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $headerPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $this->headerPartFactory = new MimeTokenPartFactory($charsetConverter);
    }

    public function testNewInstance() : void
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf(MimeToken::class, $token);
    }
}
