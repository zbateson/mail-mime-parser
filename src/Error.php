<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Exception;
use Psr\Log\LogLevel;

/**
 *
 *
 * @author Zaahid Bateson
 */
class Error
{
    protected $message;
    protected $psrLevel;
    protected $object;
    protected $exception;

    private $levelMap = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    public function __construct(string $message, string $psrLogLevelAsErrorLevel, ?object $object = null, ?Exception $exception = null)
    {
        $this->message = $message;
        $this->psrLevel = $psrLogLevelAsErrorLevel;
        $this->object = $object;
        $this->exception = $exception;
        $this->contextName = null;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function getPsrLevel() : string
    {
        return $this->psrLevel;
    }

    public function getClass() : ?string
    {
        if ($this->object !== null) {
            return get_class($this->object);
        }
        return null;
    }

    public function getObject() : ?object
    {
        return $this->object;
    }

    public function getException() : ?Exception
    {
        return $this->exception;
    }

    public function isPsrLevelGreaterOrEqualTo(string $minLevel) : bool
    {
        $intLevel = $this->levelMap[$minLevel] ?? 1000;
        $thisLevel = $this->levelMap[$this->psrLevel] ?? 1000;
        return ($thisLevel <= $intLevel);
    }
}
