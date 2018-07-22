<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

use Psr\Http\Message\StreamInterface;
use ZBateson\MailMimeParser\Stream\StreamDecoratorFactory;

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
     * @var StreamInterface the content stream after attaching encoding filters
     */
    protected $filteredStream;
    
    /**
     * @var StreamInterface the underlying content stream without filters
     *      applied
     */
    protected $stream;
    
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
     * @var StreamDecoratorFactory used to apply psr7 stream decorators to the
     *      attached StreamInterface based on encoding.
     */
    private $streamDecoratorFactory;
    
    /**
     * @var string name of stream filter handling character set conversion
     */
    private $charsetConversionFilter;
    
    /**
     * Sets up filter names used for stream_filter_append
     * 
     * @param StreamDecoratorFactory $streamDecoratorFactory
     */
    public function __construct(StreamDecoratorFactory $streamDecoratorFactory)
    {
        $this->streamDecoratorFactory = $streamDecoratorFactory;
        $this->charsetConversionFilter = '';
    }

    /**
     * Sets the URL used to open the content resource handle.
     * 
     * The function also closes the currently attached handle if any.
     * 
     * @param StreamInterface $stream
     */
    public function setStream(StreamInterface $stream = null)
    {
        $this->stream = $stream;
        $this->filteredStream = null;
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
        if ($this->filteredStream !== null) {
            $this->encoding['type'] = $transferEncoding;
            switch ($transferEncoding) {
                case 'base64':
                    $this->filteredStream = $this->streamDecoratorFactory->newBase64Stream($this->filteredStream);
                    break;
                case 'x-uuencode':
                    $this->filteredStream = $this->streamDecoratorFactory->newUUStream($this->filteredStream);
                    break;
                case 'quoted-printable':
                    $this->filteredStream = $this->streamDecoratorFactory->newQuotedPrintableStream($this->filteredStream);
                    break;
            }
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
        if ($this->filteredStream !== null) {
            if (!empty($fromCharset) && !empty($toCharset)) {
                $this->filteredStream = $this->streamDecoratorFactory->newCharsetStream(
                    $this->filteredStream,
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
        if ($this->filteredStream !== null && $this->filteredStream !== $this->stream) {
            $this->filteredStream->close();
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
        $this->filteredStream = $this->stream;
    }
    
    /**
     * Checks what transfer-encoding decoder filters and charset conversion
     * filters are attached on the handle, closing/reopening the handle if
     * different, before attaching relevant filters for the passed
     * $transferEncoding and charset arguments, and returning a StreamInterface.
     * 
     * @param string $transferEncoding
     * @param string $fromCharset the character set the content is encoded in
     * @param string $toCharset the target encoding to return
     * @return StreamInterface
     */
    public function getContentStream($transferEncoding, $fromCharset, $toCharset)
    {
        if ($this->stream === null) {
            return null;
        }
        if ($this->filteredStream === null
            || $this->isTransferEncodingFilterChanged($transferEncoding)
            || $this->isCharsetFilterChanged($fromCharset, $toCharset)) {
            $this->reset();
            $this->attachTransferEncodingFilter($transferEncoding);
            $this->attachCharsetFilter($fromCharset, $toCharset);
        }
        $this->filteredStream->rewind();
        return $this->filteredStream;
    }
}
