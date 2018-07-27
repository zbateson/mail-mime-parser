<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header;

use ArrayIterator;
use IteratorAggregate;
use ZBateson\MailMimeParser\Header\HeaderFactory;

/**
 * Maintains a collection of headers for a part.
 *
 * @author Zaahid Bateson
 */
class HeaderContainer implements IteratorAggregate
{
    /**
     * @var HeaderFactory the HeaderFactory object used for created headers
     */
    protected $headerFactory;

    private $headerObjects = [];
    private $headers = [];
    private $headerMap = [];

    private $nextIndex = 0;

    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }

    /**
     * Returns the string in lower-case, and with non-alphanumeric characters
     * stripped out.
     *
     * @param string $header
     * @return string
     */
    private function getNormalizedHeaderName($header)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($header));
    }

    public function exists($name, $offset = 0)
    {
        $s = $this->getNormalizedHeaderName($name);
        return isset($this->headerMap[$s][$offset]);
    }

    /**
     * Returns the AbstractHeader object for the header with the given $name
     *
     * Note that mime headers aren't case sensitive.
     *
     * @param string $name
     * @param int $offset
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     */
    public function get($name, $offset = 0)
    {
        $s = $this->getNormalizedHeaderName($name);
        if (isset($this->headerMap[$s][$offset])) {
            return $this->getByIndex($this->headerMap[$s][$offset]);
        }
        return null;
    }

    public function getAll($name)
    {
        $s = $this->getNormalizedHeaderName($name);
        $ret = [];
        if (!empty($this->headerMap[$s])) {
            foreach ($this->headerMap[$s] as $index) {
                $ret[] = $this->getByIndex($index);
            }
        }
        return $ret;
    }

    private function getByIndex($index)
    {
        if (!isset($this->headers[$index])) {
            return null;
        }
        if ($this->headerObjects[$index] === null) {
            $this->headerObjects[$index] = $this->headerFactory->newInstance(
                $this->headers[$index][0],
                $this->headers[$index][1]
            );
        }
        return $this->headerObjects[$index];
    }

    public function remove($name, $offset = 0)
    {
        $s = $this->getNormalizedHeaderName($name);
        if (isset($this->headerMap[$s][$offset])) {
            $index = $this->headerMap[$s][$offset];
            array_splice($this->headerMap[$s], $offset, 1);
            unset($this->headers[$index]);
            unset($this->headerObjects[$index]);
            return true;
        }
        return false;
    }

    public function removeAll($name)
    {
        $s = $this->getNormalizedHeaderName($name);
        if (!empty($this->headerMap[$s])) {
            foreach ($this->headerMap[$s] as $i) {
                unset($this->headers[$i]);
                unset($this->headerObjects[$i]);
            }
            $this->headerMap[$s] = [];
            return true;
        }
        return false;
    }
    
    public function add($name, $value)
    {
        $s = $this->getNormalizedHeaderName($name);
        $this->headers[$this->nextIndex] = [ $name, $value ];
        $this->headerObjects[$this->nextIndex] = null;
        if (!isset($this->headerMap[$s])) {
            $this->headerMap[$s] = [];
        }
        array_push($this->headerMap[$s], $this->nextIndex);
        $this->nextIndex++;
    }

    public function set($name, $value, $offset = 0)
    {
        $s = $this->getNormalizedHeaderName($name);
        if (!isset($this->headerMap[$s][$offset])) {
            $this->add($name, $value);
            return;
        }
        $i = $this->headerMap[$s][$offset];
        $this->headers[$i] = [ $name, $value ];
        $this->headerObjects[$i] = null;
    }

    public function getHeaders()
    {
        return array_values(array_filter($this->headers));
    }

    public function getIterator()
    {
        return new ArrayIterator($this->getHeaders());
    }
}
