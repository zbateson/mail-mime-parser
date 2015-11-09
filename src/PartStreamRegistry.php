<?php
namespace ZBateson\MailMimeParser;

/**
 * Factory class for PartStream objects and registration class for Message
 * handles.
 * 
 * PartStreamRegistry is used for \ZBateson\MailMimeParser\MessageParser to
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
     * @var array Array of PartStream handles with message IDs as keys.
     */
    private $registeredPartStreamHandles;

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
        }
    }

    /**
     * Unregisters the given message ID.
     * 
     * @param string $id
     */
    public function unregister($id)
    {
        unset($this->registeredHandles[$id], $this->registeredPartStreamHandles);
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

    /**
     * Creates a part stream handle for the start and end position of the
     *  message stream, and attaches it to the passed Part.
     * 
     * @param Part $part
     * @param Message $message
     * @param int $start
     * @param int $end
     */
    public function attachPartStreamHandle(Part $part, Message $message, $start, $end)
    {
        $id = $message->getObjectId();
        if (empty($this->registeredHandles[$id])) {
            return null;
        }
        $handle = fopen('mmp-mime-message://' . $id . '?start=' .
            $start . '&end=' . $end, 'r');
        
        $encoding = $part->getHeaderValue('Content-Transfer-Encoding');
        if (strtolower($encoding) === 'quoted-printable') {
            stream_filter_append($handle, 'convert.quoted-printable-decode', STREAM_FILTER_READ);
        } elseif (strtolower($encoding) === 'base64') {
            stream_filter_append($handle, 'convert.base64-decode', STREAM_FILTER_READ);
        }
        stream_filter_append(
            $handle,
            'mailmimeparser-encode.' . $part->getHeaderParameter('Content-Type', 'charset')
        );
        
        $this->registeredPartStreamHandles[$id] = $handle;
        $part->attachContentResourceHandle($handle);
    }
}
