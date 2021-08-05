<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ArrayAccess;
use InvalidArgumentException;
use RecursiveIterator;

/**
 * Container of IMessagePart items for a parent IMultiPart.
 *
 * @author Zaahid Bateson
 */
class PartChildrenContainer implements RecursiveIterator, ArrayAccess
{
    /**
     * @var IMessagePart[] array of child parts of the IMultiPart object that is
     *      holding this container.
     */
    protected $children;

    /**
     * @var int current key position within $children for iteration.
     */
    protected $position = 0;

    public function __construct(array $children = [])
    {
        $this->children = $children;
    }

    /**
     * Returns true if the current element is an IMultiPart and doesn't return
     * null for {@see IMultiPart::getChildIterator}.  Note that the iterator may
     * still be empty.
     * 
     * @return bool
     */
    public function hasChildren()
    {
        return ($this->current() instanceof IMultiPart
            && $this->current()->getChildIterator() !== null);
    }

    /**
     * If the current element points to an IMultiPart, its child iterator is
     * returned by calling {@see IMultiPart::getChildIterator}.
     *
     * @return RecursiveIterator|null
     */
    public function getChildren()
    {
        if ($this->current() instanceof IMultiPart) {
            return $this->current()->getChildIterator();
        }
        return null;
    }

    public function current()
    {
        return $this->offsetGet($this->position);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return $this->offsetExists($this->position);
    }

    /**
     * Adds the passed IMessagePart to the container in the passed position.
     *
     * If position is not passed or null, the part is added to the end, as the
     * last child in the container.
     *
     * @param IMessagePart $part
     * @param int $position
     */
    public function add(IMessagePart $part, $position = null)
    {
        $index = ($position === null) ? count($this->children) : $position;
        array_splice(
            $this->children,
            $index,
            0,
            [ $part ]
        );
    }

    /**
     * Removes the passed part, and returns the integer position it occupied.
     *
     * @param IMessagePart $part
     * @return type
     */
    public function remove(IMessagePart $part)
    {
        foreach ($this->children as $key => $child) {
            if ($child === $part) {
                $this->offsetUnset($key);
                return $key;
            }
        }
        return null;
    }

    public function offsetExists($offset)
    {
        return isset($this->children[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->children[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (!$value instanceof IMessagePart) {
            throw new InvalidArgumentException(
                get_class($value) . ' is not a \ZBateson\MailMimeParser\Message\IMessagePart'
            );
        }
        $index = ($offset === null) ? count($this->children) : $offset;
        $this->children[$index] = $value;
        if ($index < $this->position) {
            ++$this->position;
        }
    }

    public function offsetUnset($offset)
    {
        array_splice($this->children, $offset, 1);
        if ($this->position >= $offset) {
            --$this->position;
        }
    }
}
