<?php
namespace ZBateson\MailMimeParser\Util;

use PHPUnit_Framework_TestCase;

/**
 * Description of CharsetConverterTest
 *
 * @gropu Stream
 * @group CharsetConverter
 * @covers ZBateson\MailMimeParser\Util\CharsetConverter
 * @author Zaahid Bateson
 */
class CharsetConverterTest extends PHPUnit_Framework_TestCase
{
    public function testMbCharsetConversion()
    {
        $arr = array_unique(CharsetConverter::$mbAliases);
        $converter = new CharsetConverter();
        $first = reset($arr);
        $test = $converter->convert('This is my string', 'UTF-8', $first);
        foreach ($arr as $dest) {
            $this->assertEquals($test, $converter->convert($converter->convert($test, $first, $dest), $dest, $first));
        }
    }
    
    public function testIconvCharsetConversion()
    {
        $arr = array_unique(CharsetConverter::$iconvAliases);
        $converter = new CharsetConverter();
        $first = reset($arr);
        $test = $converter->convert('This is my string', 'UTF-8', $first);
        foreach ($arr as $dest) {
            $this->assertEquals($test, $converter->convert($converter->convert($test, $first, $dest), $dest, $first));
        }
    }
    
    public function testMbIconvMixedCharsetConversion()
    {
        $mbArr = array_unique(CharsetConverter::$mbAliases);
        $iconvArr = array_unique(CharsetConverter::$iconvAliases);
        $converter = new CharsetConverter();
        
        $mb = reset($mbArr);
        $iconv = reset($iconvArr);
        
        $testMb = $converter->convert('This is my string', 'UTF-8', $mb);
        $testIconv = $converter->convert('This is my string', 'UTF-8', $iconv);
        
        foreach ($iconvArr as $dest) {
            $this->assertEquals($testMb, $converter->convert($converter->convert($testMb, $mb, $dest), $dest, $mb));
        }
        foreach ($mbArr as $dest) {
            $this->assertEquals($testIconv, $converter->convert($converter->convert($testIconv, $iconv, $dest), $dest, $iconv));
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
        $converter = new CharsetConverter();
        foreach ($arr as $dest) {
            $this->assertEquals($test, $converter->convert($converter->convert($test, 'UTF-8', $dest), $dest, 'UTF-8'), "Testing with $dest");
        }
    }
}
