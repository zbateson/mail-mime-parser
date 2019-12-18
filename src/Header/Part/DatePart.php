<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\StreamDecorators\Util\CharsetConverter;
use DateTime;
use Exception;

/**
 * Parses a header into a DateTime object.
 *
 * @author Zaahid Bateson
 */
class DatePart extends LiteralPart
{
    /**
     * @var DateTime the parsed date, or null if the date could not be parsed
     */
    protected $date;
    
    /**
     * Tries parsing the header's value as an RFC 2822 date, and failing that
     * into an RFC 822 date, and failing that, tries to parse it by calling
     * new DateTime($value).
     * 
     * @param CharsetConverter $charsetConverter
     * @param string $token
     */
    public function __construct(CharsetConverter $charsetConverter, $token) {
        
        // parent::__construct converts character encoding -- may cause problems
        // sometimes.
        $dateToken = trim($token);
        parent::__construct($charsetConverter, $dateToken);

        $date = DateTime::createFromFormat(DateTime::RFC2822, $dateToken);
        if ($date === false) {
            $date = DateTime::createFromFormat(DateTime::RFC822, $dateToken);
        }
        // @see https://bugs.php.net/bug.php?id=42486
        if ($date === false && preg_match('#UT$#', $dateToken)) {
            $date = DateTime::createFromFormat(DateTime::RFC2822, $dateToken . 'C');
            if ($date === false) {
                $date = DateTime::createFromFormat(DateTime::RFC822, $dateToken . 'C');
            }
        }
        // Missing "+" in timezone definition. eg: Thu, 13 Mar 2014 15:02:47 0000
        if ($date === false && preg_match('# [0-9]{4}$#', $dateToken)) {
            $fixedDate = preg_replace('# ([0-9]{4})$#', ' +$1', $dateToken);
            $date = DateTime::createFromFormat(DateTime::RFC2822, $fixedDate);
            if ($date === false) {
                $date = DateTime::createFromFormat(DateTime::RFC822, $fixedDate);
            }
        }
        if ($date !== false) {
            if ($date->format('Y') < 50) {
                $date->setDate(intval($date->format('Y')) + 2000, $date->format('m'), $date->format('d'));
            } elseif ($date->format('Y') < 100) {
                $date->setDate(intval($date->format('Y')) + 1900, $date->format('m'), $date->format('d'));
            }
        }
        try {
            $this->date = ($date) ?: new DateTime($dateToken);
        } catch (Exception $e) {
        }
    }
    
    /**
     * Returns a DateTime object or false if it can't be parsed.
     * 
     * @return DateTime
     */
    public function getDateTime()
    {
        return $this->date;
    }
}
