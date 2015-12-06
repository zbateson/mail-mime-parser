<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header\Part;

use DateTime;

/**
 * Parses a header into a DateTime object.
 *
 * @author Zaahid Bateson
 */
class DatePart extends LiteralPart
{
    /**
     * @var DateTime the parsed date
     */
    protected $date;
    
    /**
     * Tries parsing the header's value as an RFC 2822 date, and failing that
     * into an RFC 822 date.
     * 
     * @param string $token
     */
    public function __construct($token) {
        parent::__construct(trim($token));
        $this->date = DateTime::createFromFormat(DateTime::RFC2822, $this->value);
        if ($this->date === false) {
            $this->date = DateTime::createFromFormat(DateTime::RFC822, $this->value);
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
