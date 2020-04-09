# zbateson/mail-mime-parser

Testable and PSR-compliant mail mime parser alternative to PHP's imap* functions and Pear libraries for reading messages in _Internet Message Format_ [RFC 822](http://tools.ietf.org/html/rfc822) (and later revisions [RFC 2822](http://tools.ietf.org/html/rfc2822), [RFC 5322](http://tools.ietf.org/html/rfc5322)).

[![Build Status](https://travis-ci.org/zbateson/mail-mime-parser.svg?branch=master)](https://travis-ci.org/zbateson/mail-mime-parser)
[![Code Coverage](https://scrutinizer-ci.com/g/zbateson/mail-mime-parser/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/zbateson/mail-mime-parser/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zbateson/mail-mime-parser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zbateson/mail-mime-parser/?branch=master)
[![Total Downloads](https://poser.pugx.org/zbateson/mail-mime-parser/downloads)](https://packagist.org/packages/zbateson/mail-mime-parser)
[![Latest Stable Version](https://poser.pugx.org/zbateson/mail-mime-parser/version)](https://packagist.org/packages/zbateson/mail-mime-parser)

The goals of this project are to be:

* Well written
* Standards-compliant but forgiving
* Tested where possible

To include it for use in your project, please install via composer:

```
composer require zbateson/mail-mime-parser
```

## Deprecation Notice (since 1.2.1)

getContentResourceHandle, getTextResourceHandle, and getHtmlResourceHandle have all been deprecated due to #106. fread() will only return a single byte of a multibyte char, and so will cause potentially unexpected results/warnings in some cases, and psr7 streams should be used instead. Note that this deprecation doesn’t apply to getBinaryContentResourceHandle or getResourceHandle.

## Requirements

MailMimeParser requires PHP 5.4 or newer.  Tested on PHP 5.4, 5.5, 5.6, 7, 7.1, 7.2, 7.3 and 7.4 on travis.

Please note: hhvm support has been dropped as it no longer supports 'php' as of version 4.  Previous versions of hhvm may still work, but are no longer supported.

## Usage

```php
// use an instance of MailMimeParser as a class dependency
$mailParser = new \ZBateson\MailMimeParser\MailMimeParser();

$handle = fopen('file.mime', 'r');
// parse() accepts a string, resource or Psr7 StreamInterface
$message = $mailParser->parse($handle);         // returns a \ZBateson\MailMimeParser\Message
fclose($handle);

// OR: use this procedurally (Message::from also accepts a string,
// resource or Psr7 StreamInterface
$message = \ZBateson\MailMimeParser\Message::from($string);

echo $message->getHeaderValue('from');          // user@example.com
echo $message
    ->getHeader('from')                         // AddressHeader
    ->getPersonName();                          // Person Name
echo $message->getHeaderValue('subject');       // The email's subject
echo $message
    ->getHeader('to')                           // also AddressHeader
    ->getAddresses()[0]                         // AddressPart
    ->getName();                                // Person Name
echo $message
    ->getHeader('cc')                           // also AddressHeader
    ->getAddress()[0]                           // AddressPart
    ->getEmail();                               // user@example.com

echo $message->getTextContent();                // or getHtmlContent()

$att = $message->getAttachmentPart(0);          // first attachment
echo $att->getHeaderValue('Content-Type');      // e.g. "text/plain"
echo $att->getHeaderParameter(                  // value of "charset" part
    'content-type',
    'charset'
);
echo $att->getContent();                        // get the attached file's contents
$stream = $att->getContentStream();             // the file is decoded automatically
$dest = \GuzzleHttp\Psr7\stream_for(
    fopen('my-file.ext')
);
\GuzzleHttp\Psr7\copy_to_stream(
    $stream, $dest
);
// OR: more simply if saving or copying to another stream
$att->saveContent('my-file.ext');               // writes to my-file.ext
$att->saveContent($stream);                     // copies to the stream
```

## Documentation

* [About](https://mail-mime-parser.org)
* [Usage Guide](https://mail-mime-parser.org/#quick-usage-guide)
* [API Reference](https://mail-mime-parser.org/api/1.1)

## Upgrading to 1.x

* [Upgrade Guide](https://mail-mime-parser.org/upgrade-1.0)

## License

BSD licensed - please see [license agreement](https://github.com/zbateson/mail-mime-parser/blob/master/LICENSE).
