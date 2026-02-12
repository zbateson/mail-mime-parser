<?php

namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit\Framework\TestCase;
use ZBateson\MbWrapper\MbWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of MimeTokenTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(MimeToken::class)]
#[CoversClass(HeaderPart::class)]
#[Group('HeaderParts')]
#[Group('MimeToken')]
class MimeTokenTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $charsetConverter;

    protected function setUp() : void
    {
        $this->charsetConverter = new MbWrapper();
    }

    protected function assertDecoded($expected, $encodedActual) : MimeToken
    {
        $part = new MimeToken(\mmpGetTestLogger(), $this->charsetConverter, $encodedActual);
        $this->assertEquals($expected, $part->getValue());

        return $part;
    }

    public function testBasicValue() : void
    {
        $this->assertDecoded('Step', 'Step');
    }

    public function testNullLanguageAndCharset() : void
    {
        $part = $this->assertDecoded('Step', 'Step');
        $this->assertNull($part->getLanguage());
        $this->assertNull($part->getCharset());
    }

    public function testMimeEncoding() : void
    {
        $this->assertDecoded('Kilgore Trout', '=?US-ASCII?Q?Kilgore_Trout?=');
    }

    public function testDecodeEmpty() : void
    {
        $this->assertDecoded('', '=?US-ASCII?Q??=');
        $this->assertDecoded('', '=?utf-8?Q??=');
    }

    public function testMimeDecodingNullLanguageAndCharset() : void
    {
        $part = $this->assertDecoded('Kilgore Trout', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertNull($part->getLanguage());
        $this->assertEquals('US-ASCII', $part->getCharset());
    }

    public function testNonAscii() : void
    {
        $this->assertDecoded(
            'κόσμε fløde',
            '=?UTF-8?B?zrrhvbnPg868zrUgZmzDuGRl?='
        );
        $this->assertDecoded(
            'هلا هلا شخبار؟',
            '=?WINDOWS-1256?B?5eHHIOXhxyDUzsjH0b8=?='
        );
        // either I test with an extra space at the end and they're equal, or
        // the last character is missing and they're not equal - something's
        // wrong with this test ...
        $this->assertDecoded(
            'ידיעת שפה אחת אינה מספיקה ',
            '=?WINDOWS-1255?B?6ePp8vog+fTkIODn+iDg6fDkIO7x9On35CA==?='
        );
        $this->assertDecoded(
            'がんばります',
            '=?ISO-2022-JP?B?GyRCJCwkcyRQJGokXiQ5GyhC?='
        );
        $this->assertDecoded(
            'دنت كبتن والله',
            '=?CP1256?Q?=CF=E4=CA=20=DF=C8=CA=E4=20=E6=C7=E1=E1=E5?='
        );
        $this->assertDecoded(
            'في إيه يا باشا',
            '=?UTF-8?B?2YHZiiDYpdmK2Ycg2YrYpyDYqNin2LTYpw==?='
        );
        $this->assertDecoded(
            '桜',
            '=?ISO-2022-JP?B?GyRCOnkbKEI=?='
        );
        $this->assertDecoded(
            '这也不会,那也不会',
            '=?UTF-32?B?AACP2QAATl8AAE4NAABPGgAAACwAAJCjAABOXwAATg0AAE8a?='
        );
        $this->assertDecoded(
            'セミオーダー感覚で選ぶ、ジャケット',
            '=?shift_jis?B?g1qDfoNJgVuDX4FbirSKb4LFkUmC1IFBg1eDg4NQg2KDZw==?='
        );
        $this->assertDecoded('el pingüino', 'el pingüino');
    }

    public function testLanguageAndCharset() : void
    {
        $part = $this->assertDecoded(
            'bonjour mi amici',
            '=?UTF-8*fr-be?Q?bonjour_mi amici?='
        );
        $this->assertEquals('fr-be', $part->getLanguage());
        $this->assertEquals('UTF-8', $part->getCharset());
    }

    public function testErrorAddedForUnsupportedCharset() : void
    {
        $part = $this->assertDecoded(
            'Kilgore Trout',
            '=?BLAH-BLOOH?Q?Kilgore_Trout?='
        );
        $this->assertEquals('Kilgore Trout', $part->getValue());
        $errs = $part->getAllErrors();
        $this->assertCount(1, $errs);
        $err = $errs[0];
        $this->assertSame($part, $err->getObject());
        $this->assertInstanceOf(\ZBateson\MbWrapper\UnsupportedCharsetException::class, $err->getException());
    }
}
