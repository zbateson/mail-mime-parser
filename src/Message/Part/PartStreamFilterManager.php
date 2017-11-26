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
     * @var handle current opened handle if any
     */
    protected $contentHandle;
    
    /**
     * @var string the URL to open the content stream
     */
    protected $url;
    
    /**
     * @var array map of the active encoding filter on the current handle.
     */
    private $encoding = [
        'type' => null,
        'filter' => null
    ];
    
    /**
     * @var array map of the active charset filter on the current handle.
     */
    private $charset = [
        'from' => null,
        'to' => null,
        'filter' => null
    ];
    
    /**
     * @var array mapping Content-Transfer-Encoding header values to available
     *      stream filters.
     */
    private $encodingEncoderMap = [];
    
    /**
     * @var string name of stream filter handling character set conversion
     */
    private $charsetConversionFilter;
    
    /**
     * Sets up filter names used for stream_filter_append
     * 
     * @param string $quotedPrintableDecodeFilter
     * @param string $base64DecodeFilter
     * @param string $uudecodeFilter
     * @param string $charsetConversionFilter
     */
    public function __construct(
        $quotedPrintableDecodeFilter,
        $base64DecodeFilter,
        $uudecodeFilter,
        $charsetConversionFilter
    ) {
        $this->encodingEncoderMap = [
            'quoted-printable' => $quotedPrintableDecodeFilter,
            'base64' => $base64DecodeFilter,
            'x-uuencode' => $uudecodeFilter
        ];
        $this->charsetConversionFilter = $charsetConversionFilter;
    }
    
    /**
     * Closes the contentHandle if one is attached.
     */
    public function __destruct()
    {
        $this->url = null;
        if (is_resource($this->contentHandle)) {
            fclose($this->contentHandle);
        }
    }
    
    /**
     * Sets the URL used to open the content resource handle.
     * 
     * The function also closes the currently attached handle if any.
     * 
     * @param string $url
     */
    public function setContentUrl($url)
    {
        $this->url = $url;
        if (is_resource($this->contentHandle)) {
            fclose($this->contentHandle);
            $this->contentHandle = null;
        }
    }
    
    /**
     * Returns true if the attached stream filter used for decoding the content
     * on the current handle is different from the one passed as an argument.
     * 
     * @param string $transferEncoding
     * @return boolean
     */
    private function isTransferEncodingFilterChanged($transferEncoding)
    {
        return ($transferEncoding !== $this->encoding['type']);
    }
    
    /**
     * Returns true if the attached stream filter used for charset conversion on
     * the current handle is different from the one needed based on the passed 
     * arguments.
     * 
     * @param string $fromCharset
     * @param string $toCharset
     * @return boolean
     */
    private function isCharsetFilterChanged($fromCharset, $toCharset)
    {
        return ($fromCharset !== $this->charset['from']
            || $toCharset !== $this->charset['to']);
    }
    
    /**
     * Attaches a decoding filter to the attached content handle, for the passed
     * $transferEncoding.
     * 
     * @param string $transferEncoding
     */
    protected function attachTransferEncodingFilter($transferEncoding)
    {
        if ($this->contentHandle !== null) {
            if (!empty($transferEncoding) && isset($this->encodingEncoderMap[$transferEncoding])) {
                $this->encoding['filter'] = stream_filter_append(
                    $this->contentHandle,
                    $this->encodingEncoderMap[$transferEncoding],
                    STREAM_FILTER_READ
                );
            }
            $this->encoding['type'] = $transferEncoding;
        }
    }
    
    /**
     * Attaches a charset conversion filter to the attached content handle, for
     * the passed arguments.
     * 
     * @param string $fromCharset the character set the content is encoded in
     * @param string $toCharset the target encoding to return
     */
    protected function attachCharsetFilter($fromCharset, $toCharset)
    {
        if ($this->contentHandle !== null) {
            if (!empty($fromCharset) && !empty($toCharset)) {
                $this->charset['filter'] = stream_filter_append(
                    $this->contentHandle,
                    $this->charsetConversionFilter,
                    STREAM_FILTER_READ,
                    [ 'from' => $fromCharset, 'to' => $toCharset ]
                );
            }
            $this->charset['from'] = $fromCharset;
            $this->charset['to'] = $toCharset;
        }
    }
    
    /**
     * Closes the attached resource handle, resets mapped encoding and charset
     * filters, and reopens the handle seeking back to the current position.
     * 
     * Note that closing/reopening is done because of the following differences
     * discovered between hhvm (up to 3.18 at least) and php:
     * 
     *  o stream_filter_remove wasn't triggering php_user_filter's onClose
     *    callback
     *  o read operations performed after stream_filter_remove weren't calling
     *    filter on php_user_filter
     * 
     * It seems stream_filter_remove doesn't work on hhvm, or isn't implemented
     * in the same way -- so closing and reopening seems to solve that.
     */
    public function reset()
    {
        $pos = 0;
        if (is_resource($this->contentHandle)) {
            $pos = ftell($this->contentHandle);
            fclose($this->contentHandle);
            $this->contentHandle = null;
        }
        $this->encoding = [
            'type' => null,
            'filter' => null
        ];
        $this->charset = [
            'from' => null,
            'to' => null,
            'filter' => null
        ];
        if (!empty($this->url)) {
            $this->contentHandle = fopen($this->url, 'r');
            fseek($this->contentHandle, $pos);
        }
    }
    
    /**
     * Checks what transfer-encoding decoder filters and charset conversion
     * filters are attached on the handle, closing/reopening the handle if
     * different, before attaching relevant filters for the passed
     * $transferEncoding and charset arguments, and returning a resource handle.
     * 
     * @param string $transferEncoding
     * @param string $fromCharset the character set the content is encoded in
     * @param string $toCharset the target encoding to return
     */
    public function getContentHandle($transferEncoding, $fromCharset, $toCharset)
    {
        if (!is_resource($this->contentHandle)
            || $this->isTransferEncodingFilterChanged($transferEncoding)
            || $this->isCharsetFilterChanged($fromCharset, $toCharset)) {
            $this->reset();
            $this->attachTransferEncodingFilter($transferEncoding);
            $this->attachCharsetFilter($fromCharset, $toCharset);
        }
        return $this->contentHandle;
    }
}
