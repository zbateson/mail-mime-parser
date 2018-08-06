[Installation](#installation) - [Quick Guide](#quick-usage-guide) - [Api](api/1.0)

MailMimeParser is a PHP library for reading streams formatted in _Internet Message Format_ (RFC-5322, RFC-2822 and RFC-822).  MailMimeParser aims to read any mime-compliant message in any encoding, be forgiving enough to parse non-compliant messages and read large streams quickly without exhausting available memory.

Passed streams are copied to a php://temp stream and closed for quick reading.  Attachment contents aren't directly (without a user's request) loaded into memory, and streams are provided to read from if desired.

MailMimeParser doesn't depend on PHP's imap* functions, and has minimal external dependencies when included.

# zbateson/mail-mime-parser

## Installation
To include it for use in your project, install via composer:

```
composer require zbateson/mail-mime-parser
```

## Quick Usage Guide

### Parsing a stream

To parse a mime stream using zbateson/mail-mime-parser, pass a [ZBateson\MailMimeParser\MailMimeParser](ZBateson-MailMimeParser-MailMimeParser) object as a dependency to your class, and call `parse()`.  The `parse()` method accepts a string, resource handle, or Psr7 StreamInterface stream.

Alternatively for procedural/non dependency injected usage, calling `Message::from()` may be easier.  It accepts the same arguments as `parse()`
 
```php
// $resource = fopen('my-file.mime', 'r');
// ...
$parser = new \ZBateson\MailMimeParser\MailMimeParser();
$message = $parser->parse($resource);     // returns a ZBateson\MailMimeParser\Message
// alternatively:
// $string = 'an email message to load';
$message = Message::from($string);
```

### Message headers

Headers are represented by [ZBateson\MailMimeParser\Header\AbstractHeader](ZBateson-MailMimeParser-Header-AbstractHeader) and sub-classes depending on the type of header.  In general:

* [AddressHeader](ZBateson-MailMimeParser-Header-AddressHeader) is returned for headers consisting of addresses and address groups (e.g. `From:`, `To:`, `Cc:`, etc...)
* [DateHeader](ZBateson-MailMimeParser-Header-DateHeader) parses header values into a `DateTime` object (e.g. a `Date:` header)
* [ParameterHeader](ZBateson-MailMimeParser-Header-ParameterHeader) represents headers consisting of multiple name/values (e.g. `Content-Type:`)
* [GenericHeader](ZBateson-MailMimeParser-Header-GenericHeader) is used for any other header

To retrieve an AbstractHeader object, call `Message::getHeader()`.

```php
// $message = $parser->parse($resource);
// ...
$to = $message->getHeader('To');     // would return a ZBateson\MailMimeParser\Header\AddressHeader
if ($to->hasAddress('someone@example.com')) {
    // ...
}
```

For convenience, `Message::getHeaderValue()` can be used to retrieve the value of a header (for multi-part headers like email addresses, the first part's value is returned).

```php
$contentType = $message->getHeaderValue('Content-Type');
```

In addition, `Message::getHeaderParameter()` can be used as a convenience method to retrieve the value of parameter part of a `ParameterHeader`, for example:

```php
// 3rd parameter optionally defines a default return value
$charset = $message->getHeaderParameter('Content-Type', 'charset', 'us-ascii');
// as opposed to
$parameterHeader = $message->getHeader('Content-Type');
$charset = $parameterHeader->getValueFor('charset', 'us-ascii');    // 2nd parameter also optional
```

### Message parts (text, html and other attachments)

Essentially, the `\ZBateson\MailMimeParser\Message` object returned is itself a sub-class of `\ZBateson\MailMimeParser\MimePart`.  The difference between them is: MimeParts can only be added to a Message.

Internally, a Message maintains the structure of its parsed parts.  Most users will only be interested in text parts (plain or html) and attachments.  The following methods help you do just that:
* [Message::getTextStream()](ZBateson-MailMimeParser-Message#gettextstream)
* [Message::getTextContent()](ZBateson-MailMimeParser-Message#gettextcontent)
* [Message::getHtmlStream()](ZBateson-MailMimeParser-Message#gethtmlstream)
* [Message::getHtmlContent()](ZBateson-MailMimeParser-Message#gethtmlcontent)
* [Message::getAttachmentPart()](ZBateson-MailMimeParser-Message#getattachmentpart)
* [Message::getAllAttachmentParts()](ZBateson-MailMimeParser-Message#getallattachmentparts)

`MessagePart` (returned by `Message::getAttachmentPart()`) defines useful stream and content functions, e.g.:
* [MessagePart::getContentStream()](ZBateson-MailMimeParser-MimePart#getcontentstream)
* [MessagePart::getContentType()](ZBateson-MailMimeParser-MimePart#getcontenttype)
* [MessagePart::getFilename()](ZBateson-MailMimeParser-MimePart#getfilename)
* [MessagePart::getCharset()](ZBateson-MailMimeParser-MimePart#getcharset)

Example:
```php
// $message = $parser->parse($resource);
// ...
$att = $message->getAttachmentPart(0);
echo $att->getContentType();
echo $att->getContent();
```

### Reading text and html parts

As a convenient way of reading the text and HTML parts of a `Message`, use [Message::getTextStream()](ZBateson-MailMimeParser-Message#gettextstream) and [Message::getHtmlStream()](ZBateson-MailMimeParser-Message#gethtmlstream) or the shortcuts returning strings if you want strings directly [Message::getTextContent()](ZBateson-MailMimeParser-Message#gettextcontent) and [Message::getHtmlContent()](ZBateson-MailMimeParser-Message#gethtmlcontent)

```php
// $message = $parser->parse($resource);
// ...
$txtStream = $message->getTextStream();
echo $txtStream->getContents();
// or if you know you want a string:
echo $message->getTextContent();

$htmlStream = $message->getHtmlStream();
echo $htmlStream->getContents();
// or if you know you want a string:
echo $message->getHtmlContent();
```

### Documentation
* [API Reference](api/1.0)
