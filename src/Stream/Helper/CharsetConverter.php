<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http => //opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream\Helper;

/**
 * Helper class for converting strings between charsets.
 * 
 * CharasetConverter tries to convert using mb_convert_encoding when possible,
 * defining as many aliases as possible for supported encodings.  If not
 * supported, iconv is attempted.
 *
 * @author Zaahid Bateson
 */
class CharsetConverter
{
    /**
     * @var array aliased charsets supported by mb_convert_encoding.
     *      The alias is stripped of any non-alphanumeric characters (so CP367
     *      is equal to CP-367) when comparing.
     *      Some of these translations are already supported by
     *      mb_convert_encoding on "my" PHP 5.5.9, but may not be supported in
     *      other implementations or versions since they're not part of
     *      documented support.
     */
    public static $mbAliases = [
        // supported but not included in mb_list_encodings for some reason...
        'CP850' => 'CP850',
        'GB2312' => 'GB2312',
        // aliases
        '646' => 'ASCII',
        'ANSIX341968' => 'ASCII',
        'ANSIX341986' => 'ASCII',
        'CP367' => 'ASCII',
        'CSASCII' => 'ASCII',
        'IBM367' => 'ASCII',
        'ISO646US' => 'ASCII',
        'ISO646IRV1991' => 'ASCII',
        'ISOIR6' => 'ASCII',
        'US' => 'ASCII',
        'USASCII' => 'ASCII',
        'BIG5' => 'BIG-5',
        'BIG5TW' => 'BIG-5',
        'CSBIG5' => 'BIG-5',
        '1251' => 'WINDOWS-1251',
        'CP1251' => 'WINDOWS-1251',
        'WINDOWS1251' => 'WINDOWS-1251',
        '1252' => 'WINDOWS-1252',
        'CP1252' => 'WINDOWS-1252',
        'WINDOWS1252' => 'WINDOWS-1252',
        'WE8MSWIN1252' => 'WINDOWS-1252',
        '1254' => 'WINDOWS-1254',
        'CP1254' => 'WINDOWS-1254',
        'WINDOWS1254' => 'WINDOWS-1254',
        '1255' => 'ISO-8859-8',
        'CP1255' => 'ISO-8859-8',
        'ISO88598I' => 'ISO-8859-8',
        'WINDOWS1255' => 'ISO-8859-8',
        '850' => 'CP850',
        'CSPC850MULTILINGUAL' => 'CP850',
        'IBM850' => 'CP850',
        '866' => 'CP866',
        'CSIBM866' => 'CP866',
        'IBM866' => 'CP866',
        '932' => 'CP932',
        'MS932' => 'CP932',
        'MSKANJI' => 'CP932',
        '950' => 'CP950',
        'MS950' => 'CP950',
        'EUCJP' => 'EUC-JP',
        'UJIS' => 'EUC-JP',
        'EUCKR' => 'EUC-KR',
        'KOREAN' => 'EUC-KR',
        'KSC5601' => 'EUC-KR',
        'KSC56011987' => 'EUC-KR',
        'KSX1001' => 'EUC-KR',
        'GB180302000' => 'GB18030',
        // GB2312 not listed but supported
        'CHINESE' => 'GB2312',
        'CSISO58GB231280' => 'GB2312',
        'EUCCN' => 'GB2312',
        'EUCGB2312CN' => 'GB2312',
        'GB23121980' => 'GB2312',
        'GB231280' => 'GB2312',
        'ISOIR58' => 'GB2312',
        'GBK' => 'CP936',
        '936' => 'CP936',
        'ms936' => 'CP936',
        'HZGB' => 'HZ',
        'HZGB2312' => 'HZ',
        'CSISO2022JP' => 'ISO-2022-JP',
        'ISO2022JP' => 'ISO-2022-JP',
        'ISO2022JP2004' => 'ISO-2022-JP-2004',
        'CSISO2022KR' => 'ISO-2022-KR',
        'ISO2022KR' => 'ISO-2022-KR',
        'CSISOLATIN6' => 'ISO-8859-10',
        'ISO885910' => 'ISO-8859-10',
        'ISO8859101992' => 'ISO-8859-10',
        'ISOIR157' => 'ISO-8859-10',
        'L6' => 'ISO-8859-10',
        'LATIN6' => 'ISO-8859-10',
        'ISO885913' => 'ISO-8859-13',
        'ISO885914' => 'ISO-8859-14',
        'ISO8859141998' => 'ISO-8859-14',
        'ISOCELTIC' => 'ISO-8859-14',
        'ISOIR199' => 'ISO-8859-14',
        'L8' => 'ISO-8859-14',
        'LATIN8' => 'ISO-8859-14',
        'ISO885915' => 'ISO-8859-15',
        'ISO885916' => 'ISO-8859-16',
        'ISO8859162001' => 'ISO-8859-16',
        'ISOIR226' => 'ISO-8859-16',
        'L10' => 'ISO-8859-16',
        'LATIN10' => 'ISO-8859-16',
        'CSISOLATIN2' => 'ISO-8859-2',
        'ISO88592' => 'ISO-8859-2',
        'ISO885921987' => 'ISO-8859-2',
        'ISOIR101' => 'ISO-8859-2',
        'L2' => 'ISO-8859-2',
        'LATIN2' => 'ISO-8859-2',
        'CSISOLATIN3' => 'ISO-8859-3',
        'ISO88593' => 'ISO-8859-3',
        'ISO885931988' => 'ISO-8859-3',
        'ISOIR109' => 'ISO-8859-3',
        'L3' => 'ISO-8859-3',
        'LATIN3' => 'ISO-8859-3',
        'CSISOLATIN4' => 'ISO-8859-4',
        'ISO88594' => 'ISO-8859-4',
        'ISO885941988' => 'ISO-8859-4',
        'ISOIR110' => 'ISO-8859-4',
        'L4' => 'ISO-8859-4',
        'LATIN4' => 'ISO-8859-4',
        'CSISOLATINCYRILLIC' => 'ISO-8859-5',
        'CYRILLIC' => 'ISO-8859-5',
        'ISO88595' => 'ISO-8859-5',
        'ISO885951988' => 'ISO-8859-5',
        'ISOIR144' => 'ISO-8859-5',
        'ARABIC' => 'ISO-8859-6',
        'ASMO708' => 'ISO-8859-6',
        'CSISOLATINARABIC' => 'ISO-8859-6',
        'ECMA114' => 'ISO-8859-6',
        'ISO88596' => 'ISO-8859-6',
        'ISO885961987' => 'ISO-8859-6',
        'ISOIR127' => 'ISO-8859-6',
        'CSISOLATINGREEK' => 'ISO-8859-7',
        'ECMA118' => 'ISO-8859-7',
        'ELOT928' => 'ISO-8859-7',
        'GREEK' => 'ISO-8859-7',
        'GREEK8' => 'ISO-8859-7',
        'ISO88597' => 'ISO-8859-7',
        'ISO885971987' => 'ISO-8859-7',
        'ISOIR126' => 'ISO-8859-7',
        'CSISOLATINHEBREW' => 'ISO-8859-8',
        'HEBREW' => 'ISO-8859-8',
        'ISO88598' => 'ISO-8859-8',
        'ISO885981988' => 'ISO-8859-8',
        'ISOIR138' => 'ISO-8859-8',
        'CSISOLATIN5' => 'ISO-8859-9',
        'ISO88599' => 'ISO-8859-9',
        'ISO885991989' => 'ISO-8859-9',
        'ISOIR148' => 'ISO-8859-9',
        'L5' => 'ISO-8859-9',
        'LATIN5' => 'ISO-8859-9',
        'CSKOI8R' => 'KOI8-R',
        'KOI8R' => 'KOI8-R',
        '8859' => 'ISO-8859-1',
        'CP819' => 'ISO-8859-1',
        'CSISOLATIN1' => 'ISO-8859-1',
        'IBM819' => 'ISO-8859-1',
        'ISO8859' => 'ISO-8859-1',
        'ISO88591' => 'ISO-8859-1',
        'ISO885911987' => 'ISO-8859-1',
        'ISOIR100' => 'ISO-8859-1',
        'L1' => 'ISO-8859-1',
        'LATIN' => 'ISO-8859-1',
        'LATIN1' => 'ISO-8859-1',
        'CSSHIFTJIS' => 'SJIS',
        'SHIFTJIS' => 'SJIS',
        'SHIFTJIS2004' => 'SJIS-2004',
        'SJIS2004' => 'SJIS-2004',
    ];
    
    /**
     * @var array aliased charsets supported by iconv.
     */
    public static $iconvAliases = [
        // iconv aliases -- a lot of these may already be supported
        'BIG5HKSCS' => 'BIG5HKSCS',
        'HKSCS' => 'BIG5HKSCS',
        '037' => 'CP037',
        'EBCDICCPCA' => 'CP037',
        'EBCDICCPNL' => 'CP037',
        'EBCDICCPUS' => 'CP037',
        'EBCDICCPWT' => 'CP037',
        'CSIBM037' => 'CP037',
        'IBM037' => 'CP037',
        'IBM039' => 'CP037',
        '1026' => 'CP1026',
        'CSIBM1026' => 'CP1026',
        'IBM1026' => 'CP1026',
        '1140' => 'CP1140',
        'IBM1140' => 'CP1140',
        '1250' => 'CP1250',
        'WINDOWS1250' => 'CP1250',
        '1253' => 'CP1253',
        'WINDOWS1253' => 'CP1253',
        '1256' => 'CP1256',
        'WINDOWS1256' => 'CP1256',
        '1257' => 'CP1257',
        'WINDOWS1257' => 'CP1257',
        '1258' => 'CP1258',
        'WINDOWS1258' => 'CP1258',
        '424' => 'CP424',
        'CSIBM424' => 'CP424',
        'EBCDICCPHE' => 'CP424',
        'IBM424' => 'CP424',
        '437' => 'CP437',
        'CSPC8CODEPAGE437' => 'CP437',
        'IBM437' => 'CP437',
        '500' => 'CP500',
        'CSIBM500' => 'CP500',
        'EBCDICCPBE' => 'CP500',
        'EBCDICCPCH' => 'CP500',
        'IBM500' => 'CP500',
        '775' => 'CP775',
        'CSPC775BALTIC' => 'CP775',
        'IBM775' => 'CP775',
        '860' => 'CP860',
        'CSIBM860' => 'CP860',
        'IBM860' => 'CP860',
        '861' => 'CP861',
        'CPIS' => 'CP861',
        'CSIBM861' => 'CP861',
        'IBM861' => 'CP861',
        '862' => 'CP862',
        'CSPC862LATINHEBREW' => 'CP862',
        'IBM862' => 'CP862',
        '863' => 'CP863',
        'CSIBM863' => 'CP863',
        'IBM863' => 'CP863',
        '864' => 'CP864',
        'CSIBM864' => 'CP864',
        'IBM864' => 'CP864',
        '865' => 'CP865',
        'CSIBM865' => 'CP865',
        'IBM865' => 'CP865',
        '869' => 'CP869',
        'CPGR' => 'CP869',
        'CSIBM869' => 'CP869',
        'IBM869' => 'CP869',
        '949' => 'CP949',
        'MS949' => 'CP949',
        'UHC' => 'CP949',
        'ROMAN8' => 'ROMAN8',
        'HPROMAN8' => 'ROMAN8',
        'R8' => 'ROMAN8',
        'CSHPROMAN8' => 'ROMAN8',
        'ISO2022JP2' => 'ISO2022JP2',
        'THAI' => 'ISO885911',
        'ISO885911' => 'ISO885911',
        'ISO8859112001' => 'ISO885911',
        'JOHAB' => 'CP1361',
        'MS1361' => 'CP1361',
        'MACCYRILLIC' => 'MACCYRILLIC',
        'CSPTCP154' => 'PT154',
        'PTCP154' => 'PT154',
        'CP154' => 'PT154',
        'CYRILLICASIAN' => 'PT154',
        'TIS620' => 'TIS620',
        'TIS6200' => 'TIS620',
        'TIS62025290' => 'TIS620',
        'TIS62025291' => 'TIS620',
        'ISOIR166' => 'TIS620',
    ];
    
    /**
     * @var string charset to convert from
     */
    protected $fromCharset;
    
    /**
     * @var string charset to convert to
     */
    protected $toCharset;
    
    /**
     * @var boolean indicates if $fromCharset is supported by
     * mb_convert_encoding
     */
    protected $fromCharsetMbSupported = true;
    
    /**
     * @var boolean indicates if $toCharset is supported by mb_convert_encoding
     */
    protected $toCharsetMbSupported = true;
    
    /**
     * Constructs the charset converter with source/destination charsets.
     * 
     * @param string $fromCharset
     * @param string $toCharset
     */
    public function __construct($fromCharset, $toCharset)
    {
        $this->fromCharset = $this->findSupportedCharset($fromCharset, $this->fromCharsetMbSupported);
        $this->toCharset = $this->findSupportedCharset($toCharset, $this->toCharsetMbSupported);
    }
    
    /**
     * Converts the passed string's charset from $this->fromCharset to
     * $this->toCharset.
     * 
     * The function attempts to use mb_convert_encoding if possible, and falls
     * back to iconv if not.  If the source or destination character sets aren't
     * supported, a blank string is returned.
     * 
     * @param string $str
     * @return string
     */
    public function convert($str)
    {
        // there may be some mb-supported encodings not supported by iconv (on my libiconv for instance
        // HZ isn't supported), and so it may happen that failing an mb_convert_encoding, an iconv
        // may also fail even though both support an encoding separately.
        // Unfortunately there's no great way of testing what charsets are available on iconv, and
        // attempting to blindly convert the string may be too costly, as could converting first
        // to an intermediate (ASSUMPTION: may be worth testing converting to an intermediate)
        if ($str !== '') {
            if ($this->fromCharsetMbSupported && $this->toCharsetMbSupported) {
                return mb_convert_encoding($str, $this->toCharset, $this->fromCharset);
            }
            return iconv($this->fromCharset, $this->toCharset . '//TRANSLIT//IGNORE', $str);
        }
        return $str;
    }
    
    /**
     * Looks up the passed $cs in mb_list_encodings, then strips non
     * alpha-numeric characters and tries again, then failing that calls
     * findAliasedCharset.  The method returns the charset name that should be
     * used in calls to mb_convert_encoding or iconv.
     * 
     * If the charset is part of mb_list_encodings, $mbSupported is set to true.
     * 
     * @param string $cs
     * @param boolean $mbSupported
     * @return string the final charset name to use
     */
    private function findSupportedCharset($cs, &$mbSupported)
    {
        $mbSupported = true;
        $comp = strtoupper($cs);
        $available = array_map('strtoupper', mb_list_encodings());
        if (in_array($comp, $available)) {
            return $comp;
        }
        $stripped = preg_replace('/[^A-Z0-9]+/', '', $comp);
        if (in_array($stripped, $available)) {
            return $stripped;
        }
        return $this->findAliasedCharset($comp, $stripped, $mbSupported);
    }
    
    /**
     * Looks up the passed $comp and $stripped strings in self::$mbAliases, and
     * returns the mapped charset if applicable.  Otherwise calls
     * $this->findAliasedIconvCharset.
     * 
     * $mbSupported is set to false if the charset is not located in
     * self::$mbAliases.
     * 
     * @param string $comp
     * @param string $stripped
     * @param boolean $mbSupported
     * @return string the mapped charset
     */
    private function findAliasedCharset($comp, $stripped, &$mbSupported)
    {
        if (array_key_exists($comp, self::$mbAliases)) {
            return self::$mbAliases[$comp];
        } elseif (array_key_exists($stripped, self::$mbAliases)) {
            return self::$mbAliases[$stripped];
        }
        $mbSupported = false;
        return $this->findAliasedIconvCharset($comp, $stripped);
    }
    
    /**
     * Looks up the passed $comp and $stripped strings in self::$iconvAliases,
     * and returns the mapped charset if applicable.  Otherwise returns $comp.
     * 
     * @param string $comp
     * @param string $stripped
     * @return string the mapped charset (if mapped) or $comp otherwise
     */
    private function findAliasedIconvCharset($comp, $stripped)
    {
        if (array_key_exists($comp, self::$iconvAliases)) {
            return static::$iconvAliases[$comp];
        } elseif (array_key_exists($stripped, self::$iconvAliases)) {
            return static::$iconvAliases[$stripped];
        }
        return $comp;
    }
}
