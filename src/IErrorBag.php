<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser;

use Psr\Log\LogLevel;

/**
 * Represents 
 *
 * @author Zaahid Bateson
 */
interface IErrorBag
{
    /**
     * Returns a context name for the current object to help identify it in
     * logs.
     *
     * @return string
     */
    public function getErrorLoggingContextName() : string;

    /**
     * Adds the passed $error as an error on this object.
     *
     * @param Error $error
     * @return self
     */
    public function addError(Error $error) : IErrorBag;

    /**
     * Returns true if this object has an error in its error bag at or above
     * the passed $minPsrLevel (defaults to ERROR).  If $validate is true,
     * additional validation may be performed.
     *
     * @param bool $validate
     * @param string $minPsrLevel
     * @return bool
     */
    public function hasErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : bool;

    /**
     * Returns any local errors this object has at or above the passed PSR log
     * level (defaulting to LogLevel::ERROR).
     *
     * If $validate is true, additional validation may be performed on the
     * object to check for errors.
     *
     * @param bool $validate
     * @param string $minPsrLevel
     * @return array
     */
    public function getErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : array;

    /**
     * Returns true if there are errors on error bag children of this object at
     * or above the passed PSR log level (defaulting to LogLevel::ERROR).  Note
     * that this will stop after finding the first error and return, so may be
     * slightly more performant if an error actually exists over calling
     * getAllChildErrors if only interested in whether an error exists.
     *
     * Errors on the current object are not considered.  Care should be taken
     * using this if the intention is to only 'preview' a message without
     * parsing it entirely, since this will cause the whole message to be
     * parsed as it traverses children, and could be slow on messages with large
     * attachments, etc...
     *
     * If $validate is true, additional validation may be performed on children
     * to check for errors.
     *
     * @param bool $validate
     * @param string $minPsrLevel
     * @return bool
     */
    public function hasChildErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : bool;

    /**
     * Returns any errors on error bag children of this object at or above the
     * passed PSR log level (defaulting to LogLevel::ERROR).
     *
     * Errors on the current object are not included.  Care should be taken
     * using this if the intention is to only 'preview' a message without
     * parsing it entirely, since this will cause the whole message to be
     * parsed as it traverses children, and could be slow on messages with large
     * attachments, etc...
     *
     * If $validate is true, additional validation may be performed on children
     * to check for errors.
     *
     * @param bool $validate
     * @param string $minPsrLevel
     * @return array
     */
    public function getAllChildErrors(bool $validate = false, string $minPsrLevel = LogLevel::ERROR) : array;
}
