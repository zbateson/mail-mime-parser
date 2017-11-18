<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Writer;

use ZBateson\MailMimeParser\Message\MimePart;
use ZBateson\MailMimeParser\Stream\StreamLeftover;

/**
 * Writes a MimePart to a resource handle.
 * 
 * The class is responsible for writing out the headers and content of a
 * MimePart to an output stream buffer, taking care of encoding and filtering.
 * 
 * @author Zaahid Bateson
 */
class MimePartWriter
{
    /**
     * @var array default params for stream filters in
     *      setTransferEncodingFilterOnStream
     */
    private static $defaultStreamFilterParams = [
        'line-length' => 76,
        'line-break-chars' => "\r\n",
    ];
    
    /**
     * @var array map of transfer-encoding types to registered stream filter
     *      names used in setTransferEncodingFilterOnStream
     */
    private static $typeToEncodingMap = [
        'quoted-printable' => 'mmp-convert.quoted-printable-encode',
        'base64' => 'mmp-convert.base64-encode',
        'x-uuencode' => 'mailmimeparser-uuencode',
        'x-uue' => 'mailmimeparser-uuencode',
        'uuencode' => 'mailmimeparser-uuencode',
        'uue' => 'mailmimeparser-uuencode',
    ];
    
    /**
     * Returns the singleton instance for the class, instantiating it if not
     * already created.
     */
    public static function getInstance()
    {
        static $instances = [];
        $class = get_called_class();
        if (!isset($instances[$class])) {
            $instances[$class] = new static();
        }
        return $instances[$class];
    }
    
    /**
     * Writes out the headers of the passed MimePart and follows them with an
     * empty line.
     *
     * @param MimePart $part
     * @param resource $handle
     */
    public function writePartHeadersTo(MimePart $part, $handle)
    {
        $headers = $part->getHeaders();
        foreach ($headers as $header) {
            fwrite($handle, "$header\r\n");
        }
        fwrite($handle, "\r\n");
    }
    
    /**
     * Sets up a mailmimeparser-encode stream filter on the content resource 
     * handle of the passed MimePart if applicable and returns a reference to
     * the filter.
     *
     * @param MimePart $part
     * @return resource a reference to the appended stream filter or null
     */
    private function setCharsetStreamFilterOnPartStream(MimePart $part)
    {
        $handle = $part->getContentResourceHandle();
        if ($part->isTextPart()) {
            return stream_filter_append(
                $handle,
                'mailmimeparser-encode',
                STREAM_FILTER_READ,
                [
                    'charset' => 'UTF-8',
                    'to' => $part->getHeaderParameter(
                        'Content-Type',
                        'charset',
                        'ISO-8859-1'
                    )
                ]
            );
        }
        return null;
    }
    
    /**
     * Appends a stream filter on the passed MimePart's content resource handle
     * based on the type of encoding for the passed part.
     *
     * @param MimePart $part
     * @param resource $handle
     * @param StreamLeftover $leftovers
     * @return resource the stream filter
     */
    private function setTransferEncodingFilterOnStream(MimePart $part, $handle, StreamLeftover $leftovers)
    {
        $contentHandle = $part->getContentResourceHandle();
        $encoding = strtolower($part->getHeaderValue('Content-Transfer-Encoding'));
        $params = array_merge(self::$defaultStreamFilterParams, [
            'leftovers' => $leftovers,
            'filename' => $part->getHeaderParameter(
                'Content-Type',
                'name',
                'null'
            )
        ]);
        if (isset(self::$typeToEncodingMap[$encoding])) {
            return stream_filter_append(
                $contentHandle,
                self::$typeToEncodingMap[$encoding],
                STREAM_FILTER_READ,
                $params
            );
        }
        return null;
    }

    /**
     * Trims out any starting and ending CRLF characters in the stream.
     *
     * @param string $read the read string, and where the result will be written
     *        to
     * @param bool $first set to true if this is the first set of read
     *        characters from the stream (ltrims CRLF)
     * @param string $lastChars contains any CRLF characters from the last $read
     *        line if it ended with a CRLF (because they're trimmed from the
     *        end, and get prepended to $read).
     */
    private function trimTextBeforeCopying(&$read, &$first, &$lastChars)
    {
        if ($first) {
            $first = false;
            $read = ltrim($read, "\r\n");
        }
        $read = $lastChars . $read;
        $lastChars = '';
        $matches = null;
        if (preg_match('/[\r\n]+$/', $read, $matches)) {
            $lastChars = $matches[0];
            $read = rtrim($read, "\r\n");
        }
    }

    /**
     * Copies the content of the $fromHandle stream into the $toHandle stream,
     * maintaining the current read position in $fromHandle.  The passed
     * MimePart is where $fromHandle originated after setting up filters on
     * $fromHandle.
     *
     * @param MimePart $part
     * @param resource $fromHandle
     * @param resource $toHandle
     */
    private function copyContentStream(MimePart $part, $fromHandle, $toHandle)
    {
        $pos = ftell($fromHandle);
        rewind($fromHandle);
        // changed from stream_copy_to_stream because hhvm seems to stop before
        // end of file for some reason
        $lastChars = '';
        $first = true;
        while (!feof($fromHandle)) {
            $read = fread($fromHandle, 1024);
            if (strcasecmp($part->getHeaderValue('Content-Encoding'), '8bit') !== 0) {
                $read = preg_replace('/\r\n|\r|\n/', "\r\n", $read);
            }
            if ($part->isTextPart()) {
                $this->trimTextBeforeCopying($read, $first, $lastChars);
            }
            fwrite($toHandle, $read);
        }
        fseek($fromHandle, $pos);
    }

    /**
     * Writes out the content portion of the mime part based on the headers that
     * are set on the part, taking care of character/content-transfer encoding.
     *
     * @param MimePart $part
     * @param resource $handle
     */
    public function writePartContentTo(MimePart $part, $handle)
    {
        $contentHandle = $part->getContentResourceHandle();
        if ($contentHandle !== null) {
            
            $filter = $this->setCharsetStreamFilterOnPartStream($part);
            $leftovers = new StreamLeftover();
            $encodingFilter = $this->setTransferEncodingFilterOnStream(
                $part,
                $handle,
                $leftovers
            );
            $this->copyContentStream($part, $contentHandle, $handle);
            
            if ($encodingFilter !== null) {
                fflush($handle);
                stream_filter_remove($encodingFilter);
                fwrite($handle, $leftovers->encodedValue);
            }
            if ($filter !== null) {
                stream_filter_remove($filter);
            }
        }
    }

    /**
     * Writes out the MimePart to the passed resource.
     *
     * Takes care of character and content transfer encoding on the output based
     * on what headers are set.
     *
     * @param MimePart $part
     * @param resource $handle
     */
    public function writePartTo(MimePart $part, $handle)
    {
        $this->writePartHeadersTo($part, $handle);
        $this->writePartContentTo($part, $handle);
    }
}
