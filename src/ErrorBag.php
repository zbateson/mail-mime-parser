<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\LogLevel;

/**
 *
 *
 * @author Zaahid Bateson
 */
abstract class ErrorBag extends Logger implements IErrorBag
{
    private $errors = [];
    private $validated = false;

    public function getErrorLoggingContextName() : string
    {
        return static::class;
    }

    /**
     *
     * @return ErrorBag[]
     */
    abstract protected function getErrorBagChildren() : array;

    /**
     * Perform any extra validation and call 'addError'.
     */
    protected function validate() : void
    {
        // do nothing
    }

    public function addError(Error $error) : IErrorBag
    {
        $this->errors[] = $error;
        $this->getLogger()->log(
            $error->getPsrLevel(),
            '${contextName} ${message}',
            [
                'contextName' => $this->getErrorLoggingContextName(),
                'message' => $error->getMessage()
            ]
        );
        return $this;
    }

    public function getErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR): array
    {
        if ($validate && !$this->validated) {
            $this->validated = true;
            $this->validate();
        }
        return \array_values(\array_filter(
            $this->errors,
            function ($e) use ($minPsrLevel) {
                return $e->isPsrLevelGreaterOrEqualTo($minPsrLevel);
            }
        ));
    }

    public function hasErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : bool
    {
        return (count($this->getLocalErrors($validate, $minPsrLevel)) > 0);
    }

    public function getAllChildErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : array
    {
        return \array_merge(...\array_values(\array_map(
            function ($e) use ($validate, $minPsrLevel) {
                return $e->getErrors($validate, $minPsrLevel);
            },
            $this->getErrorBagChildren()
        )));
    }

    public function hasChildErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : bool
    {
        foreach ($this->getErrorBagChildren() as $ch) {
            if ($ch->hasErrors($validate, $minPsrLevel) || $ch->hasChildErrors($validate, $minPsrLevel)) {
                return true;
            }
        }
        return false;
    }
}
