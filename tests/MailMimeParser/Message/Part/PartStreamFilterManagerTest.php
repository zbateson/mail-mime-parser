<?php
namespace ZBateson\MailMimeParser\Message\Part;

use PHPUnit_Framework_TestCase;

/**
 * PartStreamFilterManagerTest
 * 
 * @group PartStreamFilterManager
 * @group MessagePart
 * @covers ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerTest extends PHPUnit_Framework_TestCase
{
    private $partStreamFilterManager = null;
    
    private $quotedPrintableFilter = 'mmp-test.quoted-printable-decode';
    private $base64Filter = 'mmp-test.base64-decode';
    private $uudecodeFilter = 'mmp-test.uudecode';
    private $charsetConversionFilter = 'mmp-test.charset-convert';
    
    protected function setUp()
    {
        stream_filter_register(
            $this->quotedPrintableFilter,
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        stream_filter_register(
            $this->base64Filter,
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        stream_filter_register(
            $this->uudecodeFilter,
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        stream_filter_register(
            $this->charsetConversionFilter,
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        
        $this->partStreamFilterManager = new PartStreamFilterManager(
            $this->quotedPrintableFilter,
            $this->base64Filter,
            $this->uudecodeFilter,
            $this->charsetConversionFilter
        );
    }
    
    public function testAttachQuotedPrintableDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->quotedPrintableFilter, $filtername);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->attachContentStreamFilters($handle, 'quoted-printable', null);

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testAttachBase64Decoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->base64Filter, $filtername);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->attachContentStreamFilters($handle, 'base64', null);

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testAttachUUEncodeDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->uudecodeFilter, $filtername);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->attachContentStreamFilters($handle, 'x-uuencode', null);

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testAttachCharsetConversionDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->charsetConversionFilter, $filtername);
                $this->assertEquals('US-ASCII', $params['charset']);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->attachContentStreamFilters($handle, null, 'US-ASCII');

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testReAttachTransferEncodingDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                if ($callCount === 0 || $callCount === 2) {
                    $this->assertEquals($this->uudecodeFilter, $filtername);
                } else {
                    $this->assertEquals($this->quotedPrintableFilter, $filtername);
                }
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                if ($closeCount === 0) {
                    $this->assertEquals($this->uudecodeFilter, $filtername);
                } elseif ($closeCount === 1) {
                    $this->assertEquals($this->quotedPrintableFilter, $filtername);
                }
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->attachContentStreamFilters($handle, 'x-uuencode', null);
        $manager->attachContentStreamFilters($handle, 'x-uuencode', null);
        $manager->attachContentStreamFilters($handle, 'x-uuencode', null);
        $manager->attachContentStreamFilters($handle, 'quoted-printable', null);
        $manager->attachContentStreamFilters($handle, 'quoted-printable', null);
        $manager->attachContentStreamFilters($handle, 'x-uuencode', null);

        $this->assertEquals(3, $callCount);
        $this->assertEquals(2, $closeCount);

        fclose($handle);
    }
    
    public function testReAttachCharsetConversionDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->charsetConversionFilter, $filtername);
                if ($callCount === 0) {
                    $this->assertEquals('US-ASCII', $params['charset']);
                } elseif ($callCount === 1) {
                    $this->assertEquals('ISO-8859-1', $params['charset']);
                } elseif ($callCount === 2) {
                    $this->assertEquals('WINDOWS-1252', $params['charset']);
                }
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                if ($closeCount === 0) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('US-ASCII', $params['charset']);
                } elseif ($closeCount === 1) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('ISO-8859-1', $params['charset']);
                }
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->attachContentStreamFilters($handle, null, 'US-ASCII');
        $manager->attachContentStreamFilters($handle, null, 'US-ASCII');
        $manager->attachContentStreamFilters($handle, null, 'US-ASCII');
        $manager->attachContentStreamFilters($handle, null, 'ISO-8859-1');
        $manager->attachContentStreamFilters($handle, null, 'ISO-8859-1');
        $manager->attachContentStreamFilters($handle, null, 'WINDOWS-1252');

        $this->assertEquals(3, $callCount);
        $this->assertEquals(2, $closeCount);
        
        fclose($handle);
    }
    
    public function testAttachCharsetConversionAndTransferEncodingDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                
                // transfer-encoding filter must be applied before charset conversion
                if ($callCount === 0) {
                    $this->assertEquals($this->quotedPrintableFilter, $filtername);
                } elseif ($callCount === 1) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('US-ASCII', $params['charset']);
                }
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');

        $this->assertEquals(2, $callCount);
        $this->assertEquals(0, $closeCount);
        
        fclose($handle);
    }
    
    public function testReset()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');
        $manager->reset();

        $this->assertEquals(2, $callCount);
        $this->assertEquals(2, $closeCount);
        
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');
        
        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
        
        fclose($handle);
    }
    
    public function testResetByAttachingDifferentHandle()
    {
        $handle = fopen('php://memory', 'r');
        $handle2 = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');
        $manager->attachContentStreamFilters($handle2, 'quoted-printable', 'US-ASCII');

        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
        
        fclose($handle);
        fclose($handle2);
    }
}
