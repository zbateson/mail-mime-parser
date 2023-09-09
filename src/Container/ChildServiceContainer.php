<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */

namespace ZBateson\MailMimeParser\Container;

use ZBateson\MailMimeParser\ServiceLocator;
use Pimple\Container;
use Pimple\Exception\UnknownIdentifierException;

/**
 *
 * @author Zaahid Bateson
 */
class ChildServiceContainer extends ServiceLocator
{
    /**
     * @var PimpleContainer
     */
    protected $parent;

    public function __construct(Container $parent, array $extensions = [])
    {
        $this->parent = $parent;
        foreach ($extensions as $ext) {
            $this->register($ext);
        }
    }

    /**
     * Overridden to see if the class can be auto-registered and return true if
     * it can.
     */
    public function offsetExists($id) : bool
    {
        $exists = parent::offsetExists($id);
        if (!$exists) {
            return $this->parent->exists($id);
        }
        return $exists;
    }

    /**
     * Overridden to see if the class can be auto-registered and return an
     * instance if it can.
     *
     * @param string | int $id
     *
     * @throws UnknownIdentifierException
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($id)
    {
        try {
            return parent::offsetGet($id);
        } catch (UnknownIdentifierException $e) {
            return $this->parent->offsetGet($id);
        }
    }

    /**
     * Overridden to see if the class can be auto-registered first before
     * calling Pimple\Container::extend
     *
     * @param string $id
     * @param callable $callable
     * @return callable the wrapped $callable
     */
    public function extend($id, $callable)
    {
        try {
            return parent::extend($id, $callable);
        } catch (UnknownIdentifierException $e) {
            $raw = $this->parent->raw($id);
            $this[$id] = $raw;
            return parent::extend($id, $callable);
        }
    }
}
