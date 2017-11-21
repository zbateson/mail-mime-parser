<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

/**
 * Factory class for PartStream objects and registration class for Message
 * handles.
 * 
 * PartStreamRegistry is used for \ZBateson\MailMimeParser\Message\MessageParser to
 * register Message stream handles for opening with PartStreams, and to open
 * file handles for specific mime parts of a message.  The PartStreamRegistry
 * maintains a list of opened resources, closing them either when unregistering
 * a Message or on destruction.
 *
 * @author Zaahid Bateson
 */
class PartStreamRegistry
{
    /**
     * @var array Array of handles, with message IDs as keys.
     */
    private $registeredHandles;
    
    /**
     * @var int[] number of registered part stream handles with message IDs as
     * keys
     */
    private $numRefCountsForHandles;

    /**
     * Registers an ID for the passed resource handle.
     * 
     * @param string $id
     * @param resource $handle
     */
    public function register($id, $handle)
    {
        if (!isset($this->registeredHandles[$id])) {
            $this->registeredHandles[$id] = $handle;
            $this->numRefCountsForHandles[$id] = 0;
        }
    }

    /**
     * Unregisters the given message ID and closes the associated resource
     * handle.
     * 
     * @param string $id
     */
    protected function unregister($id)
    {
        fclose($this->registeredHandles[$id]);
        unset($this->registeredHandles[$id], $this->registeredPartStreamHandles[$id]);
    }
    
    /**
     * Increases the reference count for streams using the resource handle
     * associated with the message id.
     * 
     * @param int $messageId
     */
    public function increaseHandleRefCount($messageId)
    {
        $this->numRefCountsForHandles[$messageId] += 1;
    }
    
    /**
     * Decreases the reference count for streams using the resource handle
     * associated with the message id.  Once the reference count hits 0,
     * unregister is called.
     * 
     * @param int $messageId
     */
    public function decreaseHandleRefCount($messageId)
    {
        $this->numRefCountsForHandles[$messageId] -= 1;
        if ($this->numRefCountsForHandles[$messageId] === 0) {
            $this->unregister($messageId);
        }
    }

    /**
     * Returns the resource handle with the passed $id.
     * 
     * @param string $id
     * @return resource
     */
    public function get($id)
    {
        if (!isset($this->registeredHandles[$id])) {
            return null;
        }
        return $this->registeredHandles[$id];
    }
}
