<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

use ZBateson\MbWrapper\MbWrapper;
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
     * Tries parsing the passed token as an RFC 2822 date, and failing that into
     * an RFC 822 date, and failing that, tries to parse it by calling
     * ``` new DateTime($value) ```.
     * 
     * @param MbWrapper $charsetConverter
     * @param string $token
     */
    public function __construct(MbWrapper $charsetConverter, $token) {
        
        // parent::__construct converts character encoding -- may cause problems
        // sometimes.
        $dateToken = trim($token);
        parent::__construct($charsetConverter, $dateToken);

        $date = DateTime::createFromFormat(DateTime::RFC2822, $dateToken);
        if ($date === false) {
            $date = DateTime::createFromFormat(DateTime::RFC822, $dateToken);
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
