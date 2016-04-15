<?php
namespace ZBateson\MailMimeParser\Stream\Helper;

use PHPUnit_Framework_TestCase;

/**
 * Description of CharsetConverterTest
 *
 * @gropu Stream
 * @group CharsetConverter
 * @covers ZBateson\MailMimeParser\Stream\Helper\CharsetConverter
 * @author Zaahid Bateson
 */
class CharsetConverterTest extends PHPUnit_Framework_TestCase
{
    public function testMbCharsetConversion()
    {
        $arr = array_unique(CharsetConverter::$mbAliases);
        $test = 'This is my string';
        
        foreach ($arr as $dest) {
            $convert = new CharsetConverter('UTF-8', $dest);
            $convertBack = new CharsetConverter($dest, 'utf-8');
            $this->assertEquals($test, $convertBack->convert($convert->convert($test)), "Testing with $dest");
        }
    }
    
    public function testIconvCharsetConversion()
    {
        $arr = array_unique(CharsetConverter::$iconvAliases);
        $test = 'This is my string';
        foreach ($arr as $dest) {
            $convert = new CharsetConverter('UTF-8', $dest);
            $convertBack = new CharsetConverter($dest, 'utf-8');
            $this->assertEquals($test, $convertBack->convert($convert->convert($test)), "Testing with $dest");
        }
    }
    
    public function testSetCharsetConversions()
    {
        $arr = [
            'ISO-8859-8-I',
            'WINDOWS-1254',
            'CSPC-850-MULTILINGUAL',
            'GB18030_2000',
            'ISO_IR_157',
            'CS-ISO-LATIN-4',
            'ISO_IR_100',
        ];
        $test = 'This is my string';
        
        foreach ($arr as $dest) {
            $convert = new CharsetConverter('UTF-8', $dest);
            $convertBack = new CharsetConverter($dest, 'utf-8');
            $this->assertEquals($test, $convertBack->convert($convert->convert($test)), "Testing with $dest");
        }
    }
}
