<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use RecursiveIterator;

/**
 * Description of PartChildrenContainer
 *
 * @author Zaahid Bateson
 */
class PartChildrenContainer implements RecursiveIterator
{
    protected $children;

    protected $position = 0;

    public function __construct(array $children)
    {
        $this->children = $children;
    }

    public function hasChildren()
    {
        return ($this->current() instanceof IMultiPart
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
        return (isset($this->children[$this->position])) ? $this->children[$this->position] : null;
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
        return isset($this->children[$this->position]);
    }

    public function add(IMessagePart $part, $position = null)
    {
        array_splice(
            $this->children,
            ($position === null) ? count($this->children) : $position,
            0,
            [ $part ]
        );
    }

    public function remove(IMessagePart $part)
    {
        foreach ($this->children as $key => $child) {
            if ($child === $part) {
                array_splice($this->children, $key, 1);
                return $key;
            }
        }
        return null;
    }
}
