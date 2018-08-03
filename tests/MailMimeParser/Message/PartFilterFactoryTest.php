<?php
namespace ZBateson\MailMimeParser\Message;

use PHPUnit\Framework\TestCase;

/**
 * PartFilterFactoryTest
 *
 * @group PartFilterFactory
 * @group Message
 * @covers ZBateson\MailMimeParser\Message\PartFilterFactory
 * @covers ZBateson\MailMimeParser\Message\PartFilter
 * @author Zaahid Bateson
 */
class PartFilterFactoryTest extends TestCase
{
    protected $partFilterFactory;

    protected function setUp()
    {
        $this->partFilterFactory = new PartFilterFactory();
    }

    public function testNewFilterFromContentType()
    {
        $pf = $this->partFilterFactory->newFilterFromContentType('text/html');
        $this->assertNotNull($pf);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\PartFilter', $pf);
        $this->assertEquals(PartFilter::FILTER_OFF, $pf->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $pf->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $pf->signedpart);
        $this->assertEquals(
            [ PartFilter::FILTER_INCLUDE => [ 'Content-Type' => 'text/html' ] ],
            $pf->headers
        );
    }

    public function testNewFilterFromInlineContentType()
    {
        $pf = $this->partFilterFactory->newFilterFromInlineContentType('text/html');
        $this->assertNotNull($pf);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\PartFilter', $pf);
        $this->assertEquals(PartFilter::FILTER_OFF, $pf->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $pf->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $pf->signedpart);
        $this->assertEquals(
            [
                PartFilter::FILTER_INCLUDE => [ 'Content-Type' => 'text/html' ],
                PartFilter::FILTER_EXCLUDE => [ 'Content-Disposition' => 'attachment' ]
            ],
            $pf->headers
        );
    }

    public function testNewFilterFromDisposition()
    {
        $pf = $this->partFilterFactory->newFilterFromDisposition('inline', PartFilter::FILTER_EXCLUDE);
        $this->assertNotNull($pf);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\PartFilter', $pf);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $pf->multipart);
        $this->assertEquals(PartFilter::FILTER_OFF, $pf->textpart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $pf->signedpart);
        $this->assertEquals(
            [
                PartFilter::FILTER_INCLUDE => [ 'Content-Disposition' => 'inline' ]
            ],
            $pf->headers
        );
    }

    public function testNewFilterFromArray()
    {
        $headers = [
            PartFilter::FILTER_INCLUDE => [ 'test' => 'blah' ]
        ];
        $pf = $this->partFilterFactory->newFilterFromArray([
            'headers' => $headers,
            'multipart' => PartFilter::FILTER_EXCLUDE,
            'textpart' => PartFilter::FILTER_EXCLUDE,
            'signedpart' => PartFilter::FILTER_INCLUDE
        ]);
        $this->assertNotNull($pf);
        $this->assertInstanceOf('ZBateson\MailMimeParser\Message\PartFilter', $pf);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $pf->multipart);
        $this->assertEquals(PartFilter::FILTER_EXCLUDE, $pf->textpart);
        $this->assertEquals(PartFilter::FILTER_INCLUDE, $pf->signedpart);
        $this->assertEquals($headers, $pf->headers);
    }
}
