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
    protected function setUp()
    {
        stream_filter_register(
            'mmp-convert.quoted-printable-decode',
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        stream_filter_register(
            'mmp-convert.base64-decode',
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        stream_filter_register(
            'mailmimeparser-uudecode',
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
        stream_filter_register(
            'mailmimeparser-encode',
            'ZBateson\MailMimeParser\Message\Part\PartStreamFilterManagerTestStreamFilter'
        );
    }
    
    public function testAttachQuotedPrintableDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals('mmp-convert.quoted-printable-decode', $filtername);
                ++$callCount;
            }
        );

        $manager = new PartStreamFilterManager();
        $manager->attachContentStreamFilters($handle, 'quoted-printable', null);

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testAttachBase64Decoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals('mmp-convert.base64-decode', $filtername);
                ++$callCount;
            }
        );

        $manager = new PartStreamFilterManager();
        $manager->attachContentStreamFilters($handle, 'base64', null);

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testAttachUUEncodeDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals('mailmimeparser-uudecode', $filtername);
                ++$callCount;
            }
        );

        $manager = new PartStreamFilterManager();
        $manager->attachContentStreamFilters($handle, 'x-uuencode', null);

        $this->assertEquals(1, $callCount);
        fclose($handle);
    }
    
    public function testAttachCharsetConversionDecoder()
    {
        $handle = fopen('php://memory', 'r');
        
        $callCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCreateCallback(
            function ($filtername, $params) use (&$callCount) {
                $this->assertEquals('mailmimeparser-encode', $filtername);
                $this->assertEquals('US-ASCII', $params['charset']);
                ++$callCount;
            }
        );

        $manager = new PartStreamFilterManager();
        $manager->attachContentStreamFilters($handle, null, 'US-ASCII');

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
                    $this->assertEquals('mailmimeparser-uudecode', $filtername);
                } else {
                    $this->assertEquals('mmp-convert.quoted-printable-decode', $filtername);
                }
                ++$callCount;
            }
        );
        
        $closeCount = 0;
        PartStreamFilterManagerTestStreamFilter::setOnCloseCallback(
            function ($filtername, $params) use (&$closeCount) {
                if ($closeCount === 0) {
                    $this->assertEquals('mailmimeparser-uudecode', $filtername);
                } elseif ($closeCount === 1) {
                    $this->assertEquals('mmp-convert.quoted-printable-decode', $filtername);
                }
                ++$closeCount;
            }
        );

        $manager = new PartStreamFilterManager();
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
                $this->assertEquals('mailmimeparser-encode', $filtername);
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
                    $this->assertEquals('mailmimeparser-encode', $filtername);
                    $this->assertEquals('US-ASCII', $params['charset']);
                } elseif ($closeCount === 1) {
                    $this->assertEquals('mailmimeparser-encode', $filtername);
                    $this->assertEquals('ISO-8859-1', $params['charset']);
                }
                ++$closeCount;
            }
        );

        $manager = new PartStreamFilterManager();
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
                    $this->assertEquals('mmp-convert.quoted-printable-decode', $filtername);
                } elseif ($callCount === 1) {
                    $this->assertEquals('mailmimeparser-encode', $filtername);
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

        $manager = new PartStreamFilterManager();
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

        $manager = new PartStreamFilterManager();
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

        $manager = new PartStreamFilterManager();
        $manager->attachContentStreamFilters($handle, 'quoted-printable', 'US-ASCII');
        $manager->attachContentStreamFilters($handle2, 'quoted-printable', 'US-ASCII');

        $this->assertEquals(4, $callCount);
        $this->assertEquals(2, $closeCount);
        
        fclose($handle);
        fclose($handle2);
    }
}
