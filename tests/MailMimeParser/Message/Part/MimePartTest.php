<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of MimePartTest
 *
 * @group MimePart
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\MimePart
 * @author Zaahid Bateson
 */
class MimePartTest extends PHPUnit_Framework_TestCase
{
    private $mockHeaderFactory;
    
    protected function setUp()
    {
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
    }
    
    protected function getMockedParameterHeader($name, $value, $parameterValue = null)
    {
        $header = $this->getMockBuilder('ZBateson\MailMimeParser\Header\ParameterHeader')
            ->disableOriginalConstructor()
            ->setMethods(['getValue', 'getName', 'getValueFor', 'hasParameter'])
            ->getMock();
        $header->method('getName')->willReturn($name);
        $header->method('getValue')->willReturn($value);
        $header->method('getValueFor')->willReturn($parameterValue);
        $header->method('hasParameter')->willReturn(true);
        return $header;
    }
    
    protected function createNewMimePart(
        $handle = null,
        $contentHandle = null,
        $children = [],
        $headers = []
    ) {
        if ($handle === null) {
            $handle = fopen('php://memory', 'r');
        }
        if ($contentHandle === null) {
            $contentHandle = fopen('php://memory', 'r');
        }
        return new MimePart(
            $this->mockHeaderFactory,
            $handle,
            $contentHandle,
            $children,
            $headers
        );
    }
    
    public function testInstance()
    {
        $part = $this->createNewMimePart();
        $this->assertNotNull($part);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\MimePart', $part);
        $this->assertTrue($part->isMime());
    }
    
    public function testGetParts()
    {
        $nestedChildren = [
            $this->createNewMimePart()
        ];
        $children = [
            $this->createNewMimePart(null, null, $nestedChildren),
            $this->createNewMimePart(),
            $this->createNewMimePart(),
        ];
        $part = $this->createNewMimePart(null, null, $children);
        $this->assertEquals(5, $part->getPartCount());
        $this->assertSame($part, $part->getPart(0));
        $this->assertSame($children[0], $part->getPart(1));
        $this->assertSame($nestedChildren[0], $part->getPart(2));
        $this->assertSame($children[1], $part->getPart(3));
        $this->assertSame($children[2], $part->getPart(4));
        
        $allParts = [ $part, $children[0], $nestedChildren[0], $children[1], $children[2]];
        $this->assertEquals($allParts, $part->getAllParts());
    }
    
    public function testGetChildren()
    {
        $nestedChildren = [
            $this->createNewMimePart()
        ];
        $children = [
            $this->createNewMimePart(null, null, $nestedChildren),
            $this->createNewMimePart(),
            $this->createNewMimePart(),
        ];
        $part = $this->createNewMimePart(null, null, $children);
        $this->assertEquals(3, $part->getChildCount());
        $this->assertSame($children[0], $part->getChild(0));
        $this->assertSame($children[1], $part->getChild(1));
        $this->assertSame($children[2], $part->getChild(2));
        $this->assertEquals($children, $part->getChildParts());
    }
    
    public function testGetFilteredParts()
    {
        $nestedChildren = [
            $this->createNewMimePart()
        ];
        $children = [
            $this->createNewMimePart(null, null, $nestedChildren),
            $this->createNewMimePart(),
            $this->createNewMimePart(),
        ];
        $part = $this->createNewMimePart(null, null, $children);
        
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();
        $filterMock->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(false, true, false, true, false);
        
        $returned = $part->getAllParts($filterMock);
        $this->assertCount(2, $returned);
        $this->assertEquals([$children[0], $children[1]], $returned);
    }
    
    public function testGetFilteredChildParts()
    {
        $nestedChildren = [
            $this->createNewMimePart()
        ];
        $children = [
            $this->createNewMimePart(null, null, $nestedChildren),
            $this->createNewMimePart(),
            $this->createNewMimePart(),
        ];
        $part = $this->createNewMimePart(null, null, $children);
        
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();
        $filterMock->expects($this->exactly(3))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(false, true, false);
        
        $returned = $part->getChildParts($filterMock);
        $this->assertCount(1, $returned);
        $this->assertEquals([$children[1]], $returned);
    }
    
    public function testGetContentTypeHeaderAndParameter()
    {
        $hf = $this->mockHeaderFactory;
        $header = $this->getMockedParameterHeader('Content-Type', 'text/plain', 'utf-8');
        $hf->expects($this->exactly(1))
            ->method('newInstance')
            ->with('Content-Type', 'text/plain; charset=utf-8')
            ->willReturn($header);
        $part = $this->createNewMimePart(null, null, [], ['contenttype' => ['Content-Type', 'text/plain; charset=utf-8']]);
        $this->assertSame($header, $part->getHeader('CONTENT-TYPE'));
        $this->assertEquals('text/plain', $part->getHeaderValue('content-type'));
        $this->assertEquals('utf-8', $part->getHeaderParameter('CONTent-TyPE', 'charset'));
        $this->assertEquals('text/plain', $part->getContentType());
    }
    
    public function testGetContentDisposition()
    {
        $hf = $this->mockHeaderFactory;
        $header = $this->getMockedParameterHeader('Content-Disposition', 'attachment', 'bin-bashy.jpg');
        $hf->expects($this->exactly(1))
            ->method('newInstance')
            ->with('Content-Disposition', 'attachment; filename=bin-bashy.jpg')
            ->willReturn($header);
        $part = $this->createNewMimePart(null, null, [], ['contentdisposition' => ['Content-Disposition', 'attachment; filename=bin-bashy.jpg']]);
        $this->assertSame($header, $part->getHeader('CONTENT-DISPOSITION'));
        $this->assertEquals('attachment', $part->getContentDisposition());
    }
    
    public function testGetContentTransferEncoding()
    {
        $hf = $this->mockHeaderFactory;
        $header = $this->getMockedParameterHeader('Content-Transfer-Encoding', 'base64');
        $hf->expects($this->exactly(1))
            ->method('newInstance')
            ->with('Content-Transfer-Encoding', 'base64')
            ->willReturn($header);
        $part = $this->createNewMimePart(null, null, [], ['contenttransferencoding' => ['Content-Transfer-Encoding', 'base64']]);
        $this->assertSame($header, $part->getHeader('CONTENT-TRANSFER_ENCODING'));
        $this->assertEquals('base64', $part->getContentTransferEncoding());
    }
    
    public function testIsTextAndMultiPart()
    {
        $hf = $this->mockHeaderFactory;
        $textPlain = $this->getMockedParameterHeader('Content-Type', 'text/plain');
        $textHtml = $this->getMockedParameterHeader('Content-Type', 'text/html');
        $textCharset = $this->getMockedParameterHeader('Content-Type', 'text/css', 'utf-8');
        $textNoCharset = $this->getMockedParameterHeader('Content-Type', 'text/rtf');
        $multipart = $this->getMockedParameterHeader('Content-Type', 'multipart/related');
        $hf->expects($this->exactly(5))
            ->method('newInstance')
            ->willReturnOnConsecutiveCalls(
                $textPlain,
                $textHtml,
                $textCharset,
                $textNoCharset,
                $multipart
            );
        
        $partPlain = $this->createNewMimePart(null, null, [], ['contenttype' => ['Content-Type', 'oo-wee']]);
        $partHtml = $this->createNewMimePart(null, null, [], ['contenttype' => ['Content-Type', 'oo-wee']]);
        $partCharset = $this->createNewMimePart(null, null, [], ['contenttype' => ['Content-Type', 'oo-wee']]);
        $partNoCharset = $this->createNewMimePart(null, null, [], ['contenttype' => ['Content-Type', 'oo-wee']]);
        $partMultipart = $this->createNewMimePart(null, null, [], ['contenttype' => ['Content-Type', 'oo-wee']]);
        
        $this->assertTrue($partPlain->isTextPart());
        $this->assertTrue($partHtml->isTextPart());
        $this->assertTrue($partCharset->isTextPart());
        $this->assertFalse($partNoCharset->isTextPart());
        $this->assertFalse($partMultipart->isTextPart());
        
        $this->assertFalse($partPlain->isMultiPart());
        $this->assertFalse($partHtml->isMultiPart());
        $this->assertFalse($partCharset->isMultiPart());
        $this->assertFalse($partNoCharset->isMultiPart());
        $this->assertTrue($partMultipart->isMultiPart());
    }
    
    public function testGetPartsByMimeType()
    {
        $hf = $this->mockHeaderFactory;
        $textOwee = $this->getMockedParameterHeader('Content-Type', 'text/oweeee');
        $textHtml = $this->getMockedParameterHeader('Content-Type', 'text/html');
        $hf->expects($this->exactly(4))
            ->method('newInstance')
            ->willReturnOnConsecutiveCalls(
                $textOwee,
                $textHtml,
                $textHtml,
                $textOwee
            );
        $children = [
            $this->createNewMimePart('child1', 'owee', [], ['contenttype' => ['Content-Type', 'oo-wee']]),
            $this->createNewMimePart('child2', 'html', [], ['contenttype' => ['Content-Type', 'oo-wee']]),
            $this->createNewMimePart('child3', 'html', [], ['contenttype' => ['Content-Type', 'oo-wee']]),
            $this->createNewMimePart('child4', 'owee', [], ['contenttype' => ['Content-Type', 'oo-wee']]),
        ];
        $part = $this->createNewMimePart(null, null, $children);
        
        $this->assertEquals($children[3], $part->getPartByMimeType('text/oweeee', 1));
        $this->assertEquals(2, $part->getCountOfPartsByMimeType('text/html'));
        $this->assertEquals([
            $children[1], $children[2]
        ], $part->getAllPartsByMimeType('text/html'));
    }
}
