<?php
namespace ZBateson\MailMimeParser\Header\Part;

use LegacyPHPUnit\TestCase;
use ZBateson\MbWrapper\MbWrapper;

/**
 * Description of MimeLiteralTest
 *
 * @group HeaderParts
 * @group MimeLiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\MimeLiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class MimeLiteralPartTest extends TestCase
{
    private $charsetConverter;

    protected function legacySetUp()
    {
        $this->charsetConverter = new MbWrapper();
    }

    protected function assertDecoded($expected, $encodedActual)
    {
        $part = new MimeLiteralPart($this->charsetConverter, $encodedActual);
        $this->assertEquals($expected, $part->getValue());
        return $part;
    }

    public function testBasicValue()
    {
        $this->assertDecoded('Step', 'Step');
    }

    public function testNullLanguage()
    {
        $part = $this->assertDecoded('Step', 'Step');
        $this->assertEquals([
            [ 'lang' => null, 'value' => 'Step' ]
        ], $part->getLanguageArray());
    }

    public function testMimeEncoding()
    {
        $this->assertDecoded('Kilgore Trout', '=?US-ASCII?Q?Kilgore_Trout?=');
    }

    public function testMimeEncodingNullLanguage()
    {
        $part = $this->assertDecoded('Kilgore Trout', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals([
            [ 'lang' => null, 'value' => 'Kilgore Trout' ]
        ], $part->getLanguageArray());
    }

    public function testEncodingTwoParts()
    {
        $kilgore = '=?US-ASCII?Q?Kilgore_Trout?=';
        $snow = '=?US-ASCII?Q?Jon_Snow?=';

        $this->assertDecoded(
            ' Kilgore TroutJon Snow ',
            " $kilgore   $snow "
        );
        $this->assertDecoded(
            'Kilgore TroutJon Snow',
            "{$kilgore}{$snow}"
        );
        $this->assertDecoded(
            'Kilgore Trout   Jon',
            "$kilgore   Jon"
        );
        $this->assertDecoded(
            'Kilgore   Jon Snow',
            "Kilgore   $snow"
        );
        $this->assertDecoded(
            'KilgoreJon SnowTrout',
            "Kilgore{$snow}Trout"
        );
    }

    public function testNonAscii()
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
            '=?UTF-32?B?//4AANmPAABfTgAADU4AABpPAAAsAAAAo5AAAF9OAAANTgAAGk8AAA==?='
        );
        $this->assertDecoded(
            'セミオーダー感覚で選ぶ、ジャケット',
            '=?shift_jis?B?g1qDfoNJgVuDX4FbirSKb4LFkUmC1IFBg1eDg4NQg2KDZw==?='
        );
        $this->assertDecoded('el pingüino', 'el pingüino');
        $this->assertDecoded('外為ｵﾝﾗｲﾝﾃﾞﾓ(25)(デモ)決済約定のお知らせ', '=?iso-2022-jp?Q?=1B$B300Y=1B(I5]W2]C^S=1B(B(25?=
            =?iso-2022-jp?Q?)(=1B$B%G%b=1B(B)=1B$B7h:QLsDj$N$*CN$i$;=1B(B?=');
    }

    public function testIgnoreSpacesBefore()
    {
        $part = new MimeLiteralPart($this->charsetConverter, '=?US-ASCII?Q?Kilgore_Trout?=Blah');
        $this->assertTrue($part->ignoreSpacesBefore(), 'ignore spaces before');
        $this->assertFalse($part->ignoreSpacesAfter(), 'ignore spaces after');
    }

    public function testIgnoreSpacesAfter()
    {
        $part = new MimeLiteralPart($this->charsetConverter, 'Blah=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertFalse($part->ignoreSpacesBefore(), 'ignore spaces before');
        $this->assertTrue($part->ignoreSpacesAfter(), 'ignore spaces after');
    }

    public function testIgnoreSpacesBeforeAndAfter()
    {
        $part = new MimeLiteralPart($this->charsetConverter, '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertTrue($part->ignoreSpacesBefore(), 'ignore spaces before');
        $this->assertTrue($part->ignoreSpacesAfter(), 'ignore spaces after');
    }

    public function testLanguageParts()
    {
        $this->charsetConverter = $this->getMockBuilder('ZBateson\MbWrapper\MbWrapper')
            ->setMethods(['__toString'])
            ->getMock();

        $part = $this->assertDecoded(
            'Hello and bonjour mi amici. Welcome!',
            'Hello and =?UTF-8*fr-be?Q?bonjou?= =?UTF-8*it?Q?r_mi amici?=. Welcome!'
        );
        $expectedLang = [
            [ 'lang' => null, 'value' => 'Hello and ' ],
            [ 'lang' => 'fr-be', 'value' => 'bonjou' ],
            [ 'lang' => 'it', 'value' => 'r mi amici' ],
            [ 'lang' => null, 'value' => '. Welcome!' ]
        ];
        $this->assertEquals($expectedLang, $part->getLanguageArray());
    }
}
