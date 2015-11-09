<?php
namespace ZBateson\MailMimeParser\Header\Part;

use DateTime;

/**
 * Parses a mime-header into a DateTime object.
 *
 * @author Zaahid Bateson
 */
class Date extends Literal
{
    /**
     * @var DateTime
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
