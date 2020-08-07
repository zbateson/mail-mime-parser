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
    public function __construct(MbWrapper $charsetConverter, $token)
    {
        $dateToken = trim($token);
        // parent::__construct converts character encoding -- may cause problems sometimes.
        parent::__construct($charsetConverter, $dateToken);

        // Missing "+" in timezone definition. eg: Thu, 13 Mar 2014 15:02:47 0000 (not RFC compliant)
        // Won't result in an Exception, but in a valid DateTime in year `0000` - therefore we need to check this first:
        if (preg_match('# [0-9]{4}$#', $dateToken)) {
            try {
                $this->date = new DateTime(preg_replace('# ([0-9]{4})$#', ' +$1', $dateToken));
                // $this->addParsingError('Invalid Date: "+/-" missing before timezone definition');
            } catch (Exception $e) {
            }
        }

        if (!isset($this->date))
        {
            try {
                $this->date = new DateTime($dateToken);
            } catch (Exception $e) {
            }
        }

        // @see https://bugs.php.net/bug.php?id=42486
        // TODO: Test is missing:
        if (!isset($this->date) && preg_match('#UT$#', $dateToken)) {
            try {
                $this->date = new DateTime($dateToken . 'C');
                // $this->addParsingError('Invalid Date: "C" missing from timezone "UT"'); // https://github.com/zbateson/mail-mime-parser/issues/124
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Parse date string token
     * @param string $dateToken Date token as string
     *
     * @return \DateTime|false Returns \DateTime or false on failure.
     */
    private function parseDateToken($dateToken)
    {

        // First check as RFC822 which allows only 2-digit years
        $date = DateTime::createFromFormat(DateTime::RFC822, $dateToken);
        if ($date === false) {
            $date = DateTime::createFromFormat(DateTime::RFC2822, $dateToken);
        }

        return $date;
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
