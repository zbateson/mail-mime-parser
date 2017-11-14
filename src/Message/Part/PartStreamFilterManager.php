<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Manages attached stream filters for a MessagePart's content resource handle.
 * 
 * The attached stream filters are:
 *  o Content-Transfer-Encoding filter to manage decoding from a supported
 *    encoding: quoted-printable, base64 and x-uuencode.
 *  o Charset conversion filter to convert to UTF-8
 *
 * @author Zaahid Bateson
 */
class PartStreamFilterManager
{
    /**
     * @var resource compared against the passed $handle in
     *      attachContentStreamFilter to check that the attached filters are
     *      applied to the right handle (if it were to change.)
     */
    private $cachedHandle;
    
    /**
     * @var array map of the active encoding filter active on the current
     *      PartStreamFilterManager
     */
    private $encoding = [
        'type' => null,
        'filter' => null
    ];
    
    /**
     * @var array map of the active charset filter active on the current
     *      PartStreamFilterManager
     */
    private $charset = [
        'type' => null,
        'filter' => null
    ];
    
    /**
     * @var array mapping Content-Transfer-Encoding header values to available
     *      filters.
     */
    private $encodingEncoderMap = [
        'quoted-printable' => 'mmp-convert.quoted-printable-decode',
        'base64' => 'mmp-convert.base64-decode',
        'x-uuencode' => 'mailmimeparser-uudecode'
    ];
    
    /**
     * Attaches a decoding filter to the given handle, for the passed
     * $transferEncoding if not already attached to the handle.
     * 
     * Checks the value of $this->encoding['type'] against the passed
     * $transferEncoding, and, if identical, does nothing.  Otherwise detaches
     * any attached transfer encoding decoder filters before attaching a
     * relevant one and updating $this->encoding['type'].
     * 
     * @param resource $handle
     * @param string $transferEncoding
     */
    protected function attachTransferEncodingFilter($handle, $transferEncoding)
    {
        if ($transferEncoding !== $this->encoding['type']) {
            if (is_resource($this->encoding['filter'])) {
                stream_filter_remove($this->encoding['filter']);
                $this->encoding['filter'] = null;
            }
            if (!empty($transferEncoding) && isset($this->encodingEncoderMap[$transferEncoding])) {
                $this->encoding['filter'] = stream_filter_append(
                    $handle,
                    $this->encodingEncoderMap[$transferEncoding],
                    STREAM_FILTER_READ
                );
            }
            $this->encoding['type'] = $transferEncoding;
        }
    }
    
    /**
     * Attaches a charset conversion filter to the given handle, for the passed
     * $charset if not already attached to the handle.
     * 
     * Checks the value of $this->charset['type'] against the passed $charset,
     * and, if identical, does nothing.  Otherwise detaches any attached charset
     * conversion filters before attaching a relevant one and updating
     * $this->charset['type'].
     * 
     * @param resource $handle
     * @param string $charset
     */
    protected function attachCharsetFilter($handle, $charset)
    {
        if ($charset !== $this->charset['type']) {
            if (is_resource($this->charset['filter'])) {
                stream_filter_remove($this->charset['filter']);
                $this->charset['filter'] = null;
            }
            if (!empty($charset)) {
                $this->charset['filter'] = stream_filter_append(
                    $handle,
                    'mailmimeparser-encode',
                    STREAM_FILTER_READ,
                    [ 'charset' => $charset ]
                );
            }
            $this->charset['type'] = $charset;
        }
    }
    
    /**
     * Resets attached transfer-encoding decoder and charset conversion filters
     * set for the current manager.
     */
    public function reset()
    {
        if (is_resource($this->encoding['filter'])) {
            stream_filter_remove($this->encoding['filter']);
        }
        if (is_resource($this->charset['filter'])) {
            stream_filter_remove($this->charset['filter']);
        }
        $this->encoding = [
            'type' => null,
            'filter' => null
        ];
        $this->charset = [
            'type' => null,
            'filter' => null
        ];
    }
    
    /**
     * Checks what transfer-encoding decoder filters and charset conversion
     * filters are attached on the handle, detaching them if necessary, before
     * attaching relevant filters for the passed $transferEncoding and
     * $charset.
     * 
     * @param resource $handle
     * @param string $transferEncoding
     * @param string $charset
     */
    public function attachContentStreamFilters($handle, $transferEncoding, $charset)
    {
        if ($this->cachedHandle !== $handle) {
            $this->reset();
            $this->cachedHandle = $handle;
        }
        $this->attachTransferEncodingFilter($handle, $transferEncoding);
        $this->attachCharsetFilter($handle, $charset);
    }
}
