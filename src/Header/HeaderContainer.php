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

    /**
     * @var string[][] Each element in the array is an array with its first
     * element set to the header's name, and the second its value.
     */
    private $headers = [];

    /**
     * @var \ZBateson\MailMimeParser\Header\AbstractHeader[] Each element is an
     *      AbstractHeader representing the header at the same index in the
     *      $headers array.  If an AbstractHeader has not been constructed for
     *      the header at that index, the element would be set to null.
     */
    private $headerObjects = [];

    /**
     * @var array Maps header names by their "normalized" (lower-cased,
     *      non-alphanumeric characters stripped) name to an array of indexes in
     *      the $headers array.  For example:
     *      $headerMap['contenttype] = [ 1, 4 ]
     *      would indicate that the headers in $headers[1] and $headers[4] are
     *      both headers with the name 'Content-Type' or 'contENTtype'.
     */
    private $headerMap = [];

    /**
     * @var int the next index to use for $headers and $headerObjects.
     */
    private $nextIndex = 0;

    /**
     * Constructor
     *
     * @param HeaderFactory $headerFactory
     */
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

    /**
     * Returns true if the passed header exists in this collection.
     *
     * @param string $name
     * @param int $offset
     * @return boolean
     */
    public function exists($name, $offset = 0)
    {
        $s = $this->getNormalizedHeaderName($name);
        return isset($this->headerMap[$s][$offset]);
    }

    /**
     * Returns the AbstractHeader object for the header with the given $name and
     * at the optional offset (defaulting to the first header in the collection
     * where more than one header with the same name exists).
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

    /**
     * Returns all headers with the passed name.
     *
     * @param string $name
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader[]
     */
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

    /**
     * Returns the header in the headers array at the passed 0-based integer
     * index.
     *
     * @param int $index
     * @return \ZBateson\MailMimeParser\Header\AbstractHeader
     */
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

    /**
     * Removes the header from the collection with the passed name.  Defaults to
     * removing the first instance of the header for a collection that contains
     * more than one with the same passed name.
     *
     * @param string $name
     * @param int $offset
     * @return boolean
     */
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

    /**
     * Removes all headers that match the passed name.
     *
     * @param string $name
     * @return boolean
     */
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

    /**
     * Adds the header to the collection.
     *
     * @param string $name
     * @param string $value
     */
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

    /**
     * If a header exists with the passed name, and at the passed offset if more
     * than one exists, its value is updated.
     *
     * If a header with the passed name doesn't exist at the passed offset, it
     * is created at the next available offset (offset is ignored when adding).
     *
     * @param string $name
     * @param string $value
     * @param int $offset
     */
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

    /**
     * Returns an array of AbstractHeader objects representing all headers in
     * this collection.
     *
     * @return AbstractHeader
     */
    public function getHeaderObjects()
    {
        return array_filter(array_map([ $this, 'getByIndex' ], array_keys($this->headers)));
    }

    /**
     * Returns an array of headers in this collection.  Each returned element in
     * the array is an array with the first element set to the name, and the
     * second its value:
     *
     * [
     *     [ 'Header-Name', 'Header Value' ],
     *     [ 'Second-Header-Name', 'Second-Header-Value' ],
     *     // etc...
     * ]
     *
     * @return string[][]
     */
    public function getHeaders()
    {
        return array_values(array_filter($this->headers));
    }

    /**
     * Returns an iterator to the headers in this collection.  Each returned
     * element is an array with its first element set to the header's name, and
     * the second to its value:
     *
     * [ 'Header-Name', 'Header Value' ]
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getHeaders());
    }
}
