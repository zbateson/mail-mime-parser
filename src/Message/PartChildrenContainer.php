<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ArrayAccess;
use RecursiveIterator;

/**
 * Description of PartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class PartChildrenContainer implements RecursiveIterator, ArrayAccess
{
    protected $children;

    protected $position = 0;

    public function __construct(array $children = [])
    {
        $this->children = $children;
    }

    public function hasChildren()
    {
        return ($this->current() instanceof IMimePart
            && $this->current()->getChildIterator() !== null);
    }

    public function getChildren()
    {
        if ($this->current() instanceof IMimePart) {
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

    public function add(IMessagePart $part, $position = null)
    {
        $this->offsetSet(($position === null) ? count($this->children) : $position, $part);
    }

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
        array_splice(
            $this->children,
            $offset,
            0,
            [ $value ]
        );
        if ($offset < $this->position) {
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
