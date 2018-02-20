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
    protected $filteredHandle;
    
    /**
     * @var string the URL to open the content stream
     */
    protected $handle;
    
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
     * @var type 
     */
    private $streamDecoratorFactory;

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
    public function __construct($streamDecoratorFactory)
    {
        $this->streamDecoratorFactory = $streamDecoratorFactory;
        $this->encodingEncoderMap = [
            'quoted-printable' => '',
            'base64' => '',
            'x-uuencode' => ''
        ];
        $this->charsetConversionFilter = '';
    }
    
    /**
     * Closes the contentHandle if one is attached.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
    
    /**
     * Sets the URL used to open the content resource handle.
     * 
     * The function also closes the currently attached handle if any.
     * 
     * @param resource $handle
     */
    public function setHandle($handle)
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = $handle;
        $this->filteredHandle = null;
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
        if ($this->filteredHandle !== null) {
            if (!empty($transferEncoding) && isset($this->encodingEncoderMap[$transferEncoding])) {
                if ($transferEncoding === 'base64') {
                    $this->filteredHandle = $this->streamDecoratorFactory->newBase64StreamDecorator($this->filteredHandle);
                    $this->encoding['type'] = $transferEncoding;
                    return;
                } elseif ($transferEncoding === 'x-uuencode') {
                    $this->filteredHandle = $this->streamDecoratorFactory->newUUStreamDecorator($this->filteredHandle);
                    $this->encoding['type'] = $transferEncoding;
                    return;
                } elseif ($transferEncoding === 'quoted-printable') {
                    $this->filteredHandle = $this->streamDecoratorFactory->newQuotedPrintableStreamDecorator($this->filteredHandle);
                    $this->encoding['type'] = $transferEncoding;
                    return;
                }
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
        if ($this->filteredHandle !== null) {
            if (!empty($fromCharset) && !empty($toCharset)) {
                $this->filteredHandle = $this->streamDecoratorFactory->newCharsetStreamDecorator(
                    $this->filteredHandle,
                    $fromCharset,
                    $toCharset
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
        if (is_resource($this->filteredHandle)) {
            $pos = ftell($this->handle);
            $this->filteredHandle = null;
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
        if (is_resource($this->handle)) {
            $this->filteredHandle = $this->handle;
            fseek($this->filteredHandle, $pos);
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
        if (!is_resource($this->filteredHandle)
            || $this->isTransferEncodingFilterChanged($transferEncoding)
            || $this->isCharsetFilterChanged($fromCharset, $toCharset)) {
            $this->reset();
            $this->attachTransferEncodingFilter($transferEncoding);
            $this->attachCharsetFilter($fromCharset, $toCharset);
        }
        return $this->filteredHandle;
    }
}
