<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Header;

use ZBateson\MailMimeParser\Header\Consumer\SubjectConsumerService;

/**
 * Reads a subject header.
 *
 * The subject header is unique in that it doesn't include comments or quoted
 * parts.
 *
 * @author Zaahid Bateson
 */
class SubjectHeader extends AbstractHeader
{
    public function __construct(
        SubjectConsumerService $consumerService,
        string $name,
        string $value
    ) {
        parent::__construct($consumerService, $name, $value);
    }
}
