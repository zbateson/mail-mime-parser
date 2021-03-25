<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Parser\Part;

use Iterator;
use ZBateson\MailMimeParser\Parser\ParserProxy;

/**
 * Description of ChildContainerIterator
 *
 * @author Zaahid Bateson
 */
final class ChildContainerIterator implements Iterator
{

    /**
     * @var ParserProxy
     */
    private $parserProxy;

    /**
     * @var PartChildContained[]
     */
    private $children;

    /**
     * @var int
     */
    private $keys;

    /**
     * @var bool
     */
    private $valid;

    /**
     * @var bool
     */
    private $allParsed = false;

    public function __construct(ParserProxy $parserProxy, array &$children)
    {
        $this->parserProxy = $parserProxy;
        $this->children = &$children;
        $this->keys = array_keys($children);
    }

    private function parseNextChild($lastChild = null)
    {
        if ($lastChild !== null) {
            $lastChild->ensurePartParsed();
        }
        $this->allParsed = !$this->parserProxy->readNextChild();
    }

    private function updateKeys()
    {
        if (count($this->children) !== count($this->keys)) {
            $this->keys = array_keys($this->children);
        }
    }

    public function current()
    {
        $this->valid = $this->hasCurrent();
        $current = (isset($this->children[current($this->keys)])) ? $this->children[current($this->keys)] : null;
        return (isset($this->children[current($this->keys)])) ? $this->children[current($this->keys)] : null;
    }

    public function key()
    {
        $this->updateKeys();
        return current($this->keys);
    }

    public function next()
    {
        $this->updateKeys();
        $last = $this->current();
        $this->valid = !$this->allParsed;
        if (!next($this->keys) && $this->valid) {
            $this->valid = $this->hasCurrent($last);
        }
    }

    private function hasCurrent($lastChild = null)
    {
        if (($lastChild !== null || !isset($this->children[current($this->keys)])) && !$this->allParsed) {
            $this->parseNextChild($lastChild);
            $this->updateKeys();
            if (!isset($this->children[current($this->keys)])) {
                $this->allParsed = true;
                return false;
            }
            return (end($this->keys) !== false);
        }
        return isset($this->children[current($this->keys)]);
    }

    public function rewind()
    {
        reset($this->keys);
        $this->valid = $this->hasCurrent();
    }

    public function valid()
    {
        return $this->valid;
    }

    public function isAllPartsParsed()
    {
        return $this->allParsed;
    }
}
