<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Stream;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use ZBateson\StreamDecorators\Base64StreamDecorator;
use ZBateson\StreamDecorators\QuotedPrintableStreamDecorator;
use ZBateson\StreamDecorators\UUStreamDecorator;
use ZBateson\StreamDecorators\CharsetStreamDecorator;

/**
 * Description of StreamFactory
 *
 * @author Zaahid Bateson <zbateson@gmail.com>
 */
class StreamDecoderFactory
{
    public function newBase64StreamDecorator($resource)
    {
        $stream = new Base64StreamDecorator(Psr7\stream_for($resource));
        return StreamWrapper::getResource($stream);
    }

    public function newQuotedPrintableStreamDecorator($resource)
    {
        $stream = new QuotedPrintableStreamDecorator(Psr7\stream_for($resource));
        return StreamWrapper::getResource($stream);
    }

    public function newUUStreamDecorator($resource)
    {
        $stream = new UUStreamDecorator(Psr7\stream_for($resource));
        return StreamWrapper::getResource($stream);
    }

    public function newCharsetStreamDecorator($resource, $fromCharset, $toCharset)
    {
        $stream = new CharsetStreamDecorator(Psr7\stream_for($resource), $fromCharset, $toCharset);
        return StreamWrapper::getResource($stream);
    }
}
