<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use DateTime;
use DateTimeImmutable;
use ZBateson\MailMimeParser\Header\Consumer\DateConsumerService;
use ZBateson\MailMimeParser\Header\Part\DatePart;

/**
 * Reads a DatePart value header in either RFC 2822 or RFC 822 format.
 *
 * @author Zaahid Bateson
 */
class DateHeader extends AbstractHeader
{
    public function __construct(
        DateConsumerService $consumerService,
        string $name,
        string $value
    ) {
        parent::__construct($consumerService, $name, $value);
    }

    /**
     * Convenience method returning the part's DateTime object, or null if the
     * date could not be parsed.
     *
     * @return ?DateTime The parsed DateTime object.
     */
    public function getDateTime() : ?DateTime
    {
        if (!empty($this->parts) && $this->parts[0] instanceof DatePart) {
            return $this->parts[0]->getDateTime();
        }
        return null;
    }

    /**
     * Returns a DateTimeImmutable for the part's DateTime object, or null if
     * the date could not be parsed.
     *
     * @return ?DateTimeImmutable The parsed DateTimeImmutable object.
     */
    public function getDateTimeImmutable() : ?DateTimeImmutable
    {
        $dateTime = $this->getDateTime();
        if ($dateTime !== null) {
            return DateTimeImmutable::createFromMutable($dateTime);
        }
        return null;
    }
}
