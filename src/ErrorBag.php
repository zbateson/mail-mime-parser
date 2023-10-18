<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\LogLevel;

/**
 * Provides a top-level abstract implementation of IErrorBag.
 *
 * @author Zaahid Bateson
 */
abstract class ErrorBag extends Logger implements IErrorBag
{
    /**
     * @var Error[] array of Error objects belonging to this object.
     */
    private $errors = [];

    /**
     * @var bool true once the object has been validated.
     */
    private $validated = false;

    /**
     * Returns the class name.  Override to identify objects in logs.
     *
     * @return string
     */
    public function getErrorLoggingContextName() : string
    {
        return static::class;
    }

    /**
     * Return any children IErrorBag objects.
     *
     * @return IErrorBag[]
     */
    abstract protected function getErrorBagChildren() : array;

    /**
     * Perform any extra validation and call 'addError'.
     *
     * getErrors and getAllErrors call validate() if their $validate parameter
     * is true.  validate() is only called once on an object with getErrors
     * getAllErrors.
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
        return (count($this->getErrors($validate, $minPsrLevel)) > 0);
    }

    public function getAllErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : array
    {
        $arr = \array_values(\array_map(
            function ($e) use ($validate, $minPsrLevel) {
                return $e->getAllErrors($validate, $minPsrLevel) ?? [];
            },
            $this->getErrorBagChildren()
        )) ?? [];
        return \array_merge($this->getErrors($validate, $minPsrLevel), ...$arr);
    }

    public function hasAnyErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : bool
    {
        if ($this->hasErrors($validate, $minPsrLevel)) {
            return true;
        }
        foreach ($this->getErrorBagChildren() as $ch) {
            if ($ch->hasAnyErrors($validate, $minPsrLevel)) {
                return true;
            }
        }
        return false;
    }
}