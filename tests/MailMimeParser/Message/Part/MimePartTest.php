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
    private $mockPartFilterFactory;

    protected function setUp()
    {
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockPartFilterFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilterFactory')
            ->disableOriginalConstructor()
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
    
    protected function getMockedPartBuilder()
    {
        return $this->getMockBuilder('ZBateson\MailMimeParser\Message\Part\PartBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }
    
    protected function getMockedPartBuilderWithChildren()
    {
        $pb = $this->getMockedPartBuilder();
        $children = [
            $this->getMockedPartBuilder(),
            $this->getMockedPartBuilder(),
            $this->getMockedPartBuilder()
        ];
        
        $nested = $this->getMockedPartBuilder();
        $nested->method('createMessagePart')
            ->willReturn(new MimePart(
                $this->mockHeaderFactory,
                $this->mockPartFilterFactory,
                'nested',
                $nested
            ));
        $children[0]->method('getChildren')
            ->willReturn([$nested]);
        
        foreach ($children as $key => $child) {
            $child->method('createMessagePart')
                ->willReturn(new MimePart(
                    $this->mockHeaderFactory,
                    $this->mockPartFilterFactory,
                    'child' . $key,
                    $child
                ));
        }
        $pb->method('getChildren')
            ->willReturn($children);
        return $pb;
    }
    
    public function testInstance()
    {
        $part = new MimePart(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            'habibi',
            $this->getMockedPartBuilder()
        );
        $this->assertNotNull($part);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\Part\MimePart', $part);
        $this->assertTrue($part->isMime());
    }
    
    public function testCreateChildrenAndGetChildren()
    {
        $part = new MimePart(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        $this->assertEquals(3, $part->getChildCount());
        $this->assertEquals('child0', $part->getChild(0)->getMessageObjectId());
        $this->assertEquals('child1', $part->getChild(1)->getMessageObjectId());
        $this->assertEquals('child2', $part->getChild(2)->getMessageObjectId());
        $children = [
            $part->getChild(0),
            $part->getChild(1),
            $part->getChild(2)
        ];
        $this->assertEquals($children, $part->getChildParts());
    }
    
    public function testCreateChildrenAndGetParts()
    {
        $part = new MimePart(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        $this->assertEquals(5, $part->getPartCount());
        
        $children = $part->getChildParts();
        $this->assertCount(3, $children);
        $nested = $children[0]->getChild(0);
        
        $this->assertSame($part, $part->getPart(0));
        $this->assertSame($children[0], $part->getPart(1));
        $this->assertSame($nested, $part->getPart(2));
        $this->assertSame($children[1], $part->getPart(3));
        $this->assertSame($children[2], $part->getPart(4));
        
        $this->assertEquals('habibi', $part->getPart(0)->getMessageObjectId());
        $this->assertEquals('child0', $part->getPart(1)->getMessageObjectId());
        $this->assertEquals('nested', $part->getPart(2)->getMessageObjectId());
        $this->assertEquals('child1', $part->getPart(3)->getMessageObjectId());
        $this->assertEquals('child2', $part->getPart(4)->getMessageObjectId());
        
        $allParts = [ $part, $children[0], $nested, $children[1], $children[2]];
        $this->assertEquals($allParts, $part->getAllParts());
    }
    
    public function testPartBuilderHeaders()
    {
        $hf = $this->mockHeaderFactory;
        $header = $this->getMockedParameterHeader('Content-Type', 'text/plain', 'utf-8');
        
        $pb = $this->getMockedPartBuilder();
        $pb->expects($this->once())
            ->method('getContentType')
            ->willReturn($header);
        $pb->expects($this->once())
            ->method('getRawHeaders')
            ->willReturn(['contenttype' => ['Blah', 'Blah']]);

        $hf->expects($this->never())
            ->method('newInstance');
        
        $part = new MimePart(
            $hf,
            $this->mockPartFilterFactory,
            'habibi',
            $pb
        );
        
        $this->assertSame($header, $part->getHeader('CONTENT-TYPE'));
        $this->assertEquals('text/plain', $part->getHeaderValue('content-type'));
        $this->assertEquals('utf-8', $part->getHeaderParameter('CONTent-TyPE', 'charset'));
        $this->assertEquals('text/plain', $part->getContentType());
    }
    
    public function testGetFilteredParts()
    {
        $part = new MimePart(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        
        $parts = $part->getAllParts();
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();
        $filterMock->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(false, true, false, true, false);
        
        $returned = $part->getAllParts($filterMock);
        $this->assertCount(2, $returned);
        $this->assertEquals([$parts[1], $parts[3]], $returned);
    }
    
    public function testGetFilteredChildParts()
    {
        $part = new MimePart(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        $parts = $part->getAllParts();
        
        $filterMock = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->setMethods(['filter'])
            ->getMock();
        $filterMock->expects($this->exactly(3))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(false, true, false);
        
        $returned = $part->getChildParts($filterMock);
        $this->assertCount(1, $returned);
        $this->assertEquals([$parts[3]], $returned);
    }
    
    public function testGetUnsetHeader()
    {
        $part = new MimePart(
            $this->mockHeaderFactory,
            $this->mockPartFilterFactory,
            'habibi',
            $this->getMockedPartBuilder()
        );
        $this->assertNull($part->getHeader('blah'));
        $this->assertEquals('upside-down', $part->getHeaderValue('blah', 'upside-down'));
        $this->assertEquals('demigorgon', $part->getHeaderParameter('blah', 'blah', 'demigorgon'));
    }
    
    public function testGetHeaderAndHeaderParameter()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn(['xheader' => ['X-Header', 'Some Value']]);
        
        $header = $this->getMockedParameterHeader('meen?', 'habibi', 'kochanie');
        $hf = $this->mockHeaderFactory;
        $hf->expects($this->once())
            ->method('newInstance')
            ->with('X-Header', 'Some Value')
            ->willReturn($header);
        
        $part = new MimePart(
            $hf,
            $this->mockPartFilterFactory,
            'habibi',
            $pb
        );
        $this->assertEquals($header, $part->getHeader('X-header'));
        $this->assertEquals('habibi', $part->getHeaderValue('x-HEADER'));
        $this->assertEquals('kochanie', $part->getHeaderParameter('x-header', 'anything'));
    }
    
    public function testGetContentDisposition()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contentdisposition' => ['Content-Disposition', 'attachment; filename=bin-bashy.jpg']
            ]);
        
        $header = $this->getMockedParameterHeader('meen?', 'habibi');
        $hf = $this->mockHeaderFactory;
        $hf->expects($this->once())
            ->method('newInstance')
            ->with('Content-Disposition', 'attachment; filename=bin-bashy.jpg')
            ->willReturn($header);
        
        $part = new MimePart(
            $hf,
            $this->mockPartFilterFactory,
            'habibi',
            $pb
        );
        
        $this->assertSame($header, $part->getHeader('CONTENT-DISPOSITION'));
        $this->assertEquals('habibi', $part->getContentDisposition());
    }
    
    public function testGetContentTransferEncoding()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttransferencoding' => ['Content-Transfer-Encoding', 'base64']
            ]);
        
        $header = $this->getMockedParameterHeader('meen?', 'habibi');
        $hf = $this->mockHeaderFactory;
        $hf->expects($this->once())
            ->method('newInstance')
            ->with('Content-Transfer-Encoding', 'base64')
            ->willReturn($header);
        
        $part = new MimePart(
            $hf,
            $this->mockPartFilterFactory,
            'habibi',
            $pb
        );

        $this->assertSame($header, $part->getHeader('CONTENT-TRANSFER_ENCODING'));
        $this->assertEquals('habibi', $part->getContentTransferEncoding());
    }
    
    public function testIsTextIsMultiPartForNonTextNonMultipart()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'stuff/blooh');
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }
    
    public function testIsTextForTextPlain()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'text/plain');
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isTextPart());
    }
    
    public function testIsTextForTextHtml()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'text/html');
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isTextPart());
    }
    
    public function testIsTextForTextMimeTypeWithCharset()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'text/blah', 'utf-8');
        $header->expects($this->once())
            ->method('getValueFor')
            ->with('charset');
            
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertTrue($part->isTextPart());
    }
    
    public function testIsTextForTextMimeTypeWithoutCharset()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'text/blah');
        $header->expects($this->once())
            ->method('getValueFor')
            ->with('charset');
            
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertFalse($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }
    
    public function testIsMultipartForMultipartRelated()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'multipart/related');
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertTrue($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }
    
    public function testIsMultipartForMultipartAnything()
    {
        $pb = $this->getMockedPartBuilder();
        $pb->method('getRawHeaders')
            ->willReturn([
                'contenttype' => ['Not', 'Important']
            ]);
        
        $header = $this->getMockedParameterHeader('Content-Type', 'multipart/anything');
        $hf = $this->mockHeaderFactory;
        $hf->method('newInstance')
            ->willReturn($header);
        
        $part = new MimePart($hf, $this->mockPartFilterFactory, 'habibi', $pb);
        $this->assertTrue($part->isMultiPart());
        $this->assertFalse($part->isTextPart());
    }
    
    public function testGetAllPartsByMimeType()
    {
        $hf = $this->mockHeaderFactory;
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(true, true, false, false, false);
        
        $pf->expects($this->once())
            ->method('newFilterFromContentType')
            ->with('awww geez')
            ->willReturn($filter);
        
        $part = new MimePart(
            $hf,
            $pf,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        $parts = $part->getAllPartsByMimeType('awww geez');
        $this->assertCount(2, $parts);
    }
    
    public function testGetPartByMimeType()
    {
        $hf = $this->mockHeaderFactory;
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(10))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(
                true, false, false, true, false,
                true, false, false, true, false
            );
        
        $pf->expects($this->exactly(2))
            ->method('newFilterFromContentType')
            ->with('awww geez')
            ->willReturn($filter);
        
        $part = new MimePart(
            $hf,
            $pf,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        $this->assertSame($part, $part->getPartByMimeType('awww geez'));
        $this->assertSame($part->getPart(3), $part->getPartByMimeType('awww geez', 1));
    }
    
    public function testGetCountOfPartsByMimeType()
    {
        $hf = $this->mockHeaderFactory;
        $pf = $this->mockPartFilterFactory;
        $filter = $this->getMockBuilder('ZBateson\MailMimeParser\Message\PartFilter')
            ->disableOriginalConstructor()
            ->getMock();
        $filter->expects($this->exactly(5))
            ->method('filter')
            ->willReturnOnConsecutiveCalls(true, true, false, false, true);
        
        $pf->expects($this->once())
            ->method('newFilterFromContentType')
            ->with('awww geez, Rick')
            ->willReturn($filter);
        
        $part = new MimePart(
            $hf,
            $pf,
            'habibi',
            $this->getMockedPartBuilderWithChildren()
        );
        $this->assertEquals(3, $part->getCountOfPartsByMimeType('awww geez, Rick'));
    }
}
