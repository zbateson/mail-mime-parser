<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of HeaderPartTest
 *
 * @group HeaderParts
 * @group HeaderPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class HeaderPartTest extends PHPUnit_Framework_TestCase
{
    private $abstractHeaderPartStub;
    
    protected function setUp()
    {
        $charsetConverter = $this->getMock('ZBateson\StreamDecorators\Util\CharsetConverter');
        $stub = $this->getMockBuilder('\ZBateson\MailMimeParser\Header\Part\HeaderPart')
            ->setConstructorArgs([$charsetConverter])
            ->getMockForAbstractClass();
        $this->abstractHeaderPartStub = $stub;
    }
    
    public function testIgnoreSpaces()
    {
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesBefore());
        $this->assertFalse($this->abstractHeaderPartStub->ignoreSpacesAfter());
    }
}