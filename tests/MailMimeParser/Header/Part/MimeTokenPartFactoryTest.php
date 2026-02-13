<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of MimeTokenPartFactoryTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MimeTokenPartFactory::class)]
#[Group('HeaderParts')]
#[Group('MimeTokenPartFactory')]
class MimeTokenPartFactoryTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $headerPartFactory;

    protected function setUp() : void
    {
        $charsetConverter = new MbWrapper();
        $this->headerPartFactory = new MimeTokenPartFactory(\mmpGetTestLogger(), $charsetConverter);
    }

    public function testNewInstance() : void
    {
        $token = $this->headerPartFactory->newInstance('Test');
        $this->assertNotNull($token);
        $this->assertInstanceOf(MimeToken::class, $token);
    }
}
