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
    
    private $quotedPrintableFilter = 'mmp-test-mgr.quoted-printable-decode';
    private $base64Filter = 'mmp-test-mgr.base64-decode';
    private $uudecodeFilter = 'mmp-test-mgr.uudecode';
    private $charsetConversionFilter = 'mmp-test-mgr.charset-convert';
    
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
        $this->partStreamFilterManager->setContentUrl('php://memory');
    }
    
    public function testAttachQuotedPrintableDecoder()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->quotedPrintableFilter, $filtername);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->getContentHandle('quoted-printable', null, null);

        $this->assertEquals(1, $callCount);
    }
    
    public function testAttachBase64Decoder()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->base64Filter, $filtername);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->getContentHandle('base64', null, null);

        $this->assertEquals(1, $callCount);
    }
    
    public function testAttachUUEncodeDecoder()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->uudecodeFilter, $filtername);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->getContentHandle('x-uuencode', null, null);

        $this->assertEquals(1, $callCount);
    }
    
    public function testAttachCharsetConversionDecoder()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->charsetConversionFilter, $filtername);
                $this->assertEquals('US-ASCII', $params['from']);
                $this->assertEquals('UTF-8', $params['to']);
                ++$callCount;
            }
        );

        $this->partStreamFilterManager->getContentHandle(null, 'US-ASCII', 'UTF-8');

        $this->assertEquals(1, $callCount);
    }
    
    public function testReAttachTransferEncodingDecoder()
    {
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
        $manager->getContentHandle('x-uuencode', null, null);
        $manager->getContentHandle('x-uuencode', null, null);
        $manager->getContentHandle('x-uuencode', null, null);
        $manager->getContentHandle('quoted-printable', null, null);
        $manager->getContentHandle('quoted-printable', null, null);
        $manager->getContentHandle('x-uuencode', null, null);

        $this->assertEquals(3, $callCount);
        $this->assertEquals(2, $closeCount);
    }
    
    public function testReAttachCharsetConversionDecoder()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals($this->charsetConversionFilter, $filtername);
                if ($callCount === 0) {
                    $this->assertEquals('US-ASCII', $params['from']);
                    $this->assertEquals('UTF-8', $params['to']);
                } elseif ($callCount === 1) {
                    $this->assertEquals('US-ASCII', $params['from']);
                    $this->assertEquals('WINDOWS-1252', $params['to']);
                } elseif ($callCount === 2) {
                    $this->assertEquals('ISO-8859-1', $params['from']);
                    $this->assertEquals('WINDOWS-1252', $params['to']);
                } elseif ($callCount === 3) {
                    $this->assertEquals('WINDOWS-1252', $params['from']);
                    $this->assertEquals('UTF-8', $params['to']);
                }
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                if ($closeCount === 0) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('US-ASCII', $params['from']);
                    $this->assertEquals('UTF-8', $params['to']);
                } elseif ($closeCount === 1) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('US-ASCII', $params['from']);
                    $this->assertEquals('WINDOWS-1252', $params['to']);
                } elseif ($closeCount === 2) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('ISO-8859-1', $params['from']);
                    $this->assertEquals('WINDOWS-1252', $params['to']);
                }
                ++$closeCount;
            }
        );

        $manager = $this->partStreamFilterManager;
        $manager->getContentHandle(null, 'US-ASCII', 'UTF-8');
        $manager->getContentHandle(null, 'US-ASCII', 'UTF-8');
        $manager->getContentHandle(null, 'US-ASCII', 'WINDOWS-1252');
        $manager->getContentHandle(null, 'ISO-8859-1', 'WINDOWS-1252');
        $manager->getContentHandle(null, 'ISO-8859-1', 'WINDOWS-1252');
        $manager->getContentHandle(null, 'WINDOWS-1252', 'UTF-8');

        $this->assertEquals(4, $callCount);
        $this->assertEquals(3, $closeCount);
    }
    
    public function testAttachCharsetConversionAndTransferEncodingDecoder()
    {
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                
                // transfer-encoding filter must be applied before charset conversion
                if ($callCount === 0) {
                    $this->assertEquals($this->quotedPrintableFilter, $filtername);
                } elseif ($callCount === 1) {
                    $this->assertEquals($this->charsetConversionFilter, $filtername);
                    $this->assertEquals('US-ASCII', $params['from']);
                    $this->assertEquals('UTF-8', $params['to']);
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
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-8');
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-8');
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-8');

        $this->assertEquals(2, $callCount);
        $this->assertEquals(0, $closeCount);
    }
    
    public function testReset()
    {
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
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-8');
        $manager->reset();

        $this->assertEquals(2, $callCount);
        $this->assertEquals(2, $closeCount);
        
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-8');
        
        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
    }
    
    public function testResetByAttachingDifferentHandle()
    {
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
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-16');
        $manager->setContentUrl('php://temp');
        $manager->getContentHandle('quoted-printable', 'US-ASCII', 'UTF-16');

        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
    }
}
