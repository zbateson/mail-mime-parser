[About](#) - [Sponsors](/#sponsors) - [Basics](#basics-introduction) - [Usage Guide](#usage-guide) - [API Documentation](api/2.0) -
[Upgrading to 2.0](upgrade-2.0) - [Contributors](#contributors)

# zbateson/mail-mime-parser

A stable, standards-compliant, easy-to-use, on-demand, email message parsing
library for PHP.

To include it for use in your project, install via composer:

```
composer require zbateson/mail-mime-parser
```

## Sponsors

[![SecuMailer](sponsors/logo-secumailer.png)](https://secumailer.com)

A huge thank you to [all my sponsors](https://github.com/sponsors/zbateson). <3

If this project's helped you, please consider [sponsoring me](https://github.com/sponsors/zbateson).

## Removal Notice (since 2.0.0)

getContentResourceHandle, getTextResourceHandle, and getHtmlResourceHandle have all been deprecated in 1.2.1 and removed in 2.0.0. fread() will only return a single byte of a multibyte char, and so will cause potentially unexpected results/warnings in some cases, and psr7 streams should be used instead. Note that getBinaryContentResourceHandle and getResourceHandle are still available.

## Change in 2.0

Starting in 2.0, the passed resource handle or stream must remain open while the returned message object is still in use.

Old code:
```php
$handle = fopen('file.mime', 'r');
$message = $mailParser->parse($handle);         // returns `Message`
fclose($handle);
```

New code:
```php
// attaches the resource handle to the returned `IMessage` if the second parameter
// is true.  The resource handle is closed when the IMessage is destroyed.
$message = $mailParser->parse(fopen('file.mime', 'r'), true);
```

For a more complete list of changes, please visit the [2.0 Upgrade Guide](https://mail-mime-parser.org/upgrade-2.0).

## Basics/Introduction

MailMimeParser has minimal external dependencies.  It requires mbstring to be
installed and configured, and works better with iconv being available as well.

It also makes use of the pimple/pimple library for dependency injection and
guzzlehttp/psr7 for streams.  There are two additional sister libraries that
are used as well: zbateson/stream-decorators which provides wrappers for psr7
streams, and zbateson/mb-wrapper for charset conversion and some string
manipulation routines.

```php
use ZBateson\MailMimeParser\Message;

$message = Message::parse($handleOrStreamOrString, true);
$subject = $message->getHeaderValue('Subject');
$text = $message->getTextContent();
$html = $message->getHtmlContent();
$from = $message->getHeader('From');
$fromName = $from->getName();
$fromEmail = $from->getEmail();

$to = $message->getHeader('To');
// first email address can be accessed directly
$firstToName = $to->getName();
$firstToEmail = $to->getEmail();

foreach ($to->getAllAddresses() as $addr) {
    $toName = $to->getName();
    $toEmail = $to->getEmail();
}

$attachment = $message->getAttachmentPart(0);
$fname = $attachment->getFilename();
$stream = $attachment->getContentStream();
$attachment->saveContent('destination-file.ext');
```

There's no need to worry about the Content-Transfer-Encoding, or how the name in
an email address is encoded, or what charset was used.

And, unlike most other available email parsing libraries, MailMimeParser is its
own "parser".  It does not use PHP's imap* functions or the pecl mailparse
extension.

There are numerous advantages over other libraries:

* Handles header decoding/charset/formats for you. No need to worry about the
  format a header is in, if it's RFC2047 or RFC2231, contains nested comments,
  email lists, multiple lines, or combinations thereof. Most imap or mailparse
  users rely on regex patterns to decode parts of a header, and end up ignoring
  some of the complexities that can arise.
* Handles content decoding and charset conversion for you. No need to worry
  about whether the content is base64 encoded and using WINDOWS-1256 charset
  encoding (so long as mb_* or iconv* support the charset, or I've identified
  it as an alias for a supported charset).
* Supports all of RFC-5322, RFC-2822 and RFC-822, and tries to be as forgiving
  as possible for incorrectly formatted messages.
* Parses messages into a Message object with handy methods like getContent(),
  getHeader(), rather than a confusing array posing as an object.
* As of 1.0, can handle multiple headers of the same name (unlike mailparse or
  any derivative).
* As of 2.0, parses parts of a message on-demand, so reading the subject line
  from a very large message can be very fast, or reading just the text body
  without the attachments is also very fast.
* Can edit a message by setting headers, overwriting content, changing to/from
  multipart/alternative or mixed, add/remove attachments, set the message as
  multipart/signed (signing functionality not included... the library can
  convert the message and provide the part of the message that needs to be
  signed).
* Uses streams internally to avoid keeping everything in memory.
* PSR-compliant, unit and functionally tested.


## Usage Guide

> For the 0.4 usage guide, [click here](usage-guide-0.4.html)
> For the 1.0 usage guide, [click here](usage-guide-1.0.html)

### Parsing an email

To parse an email using zbateson/mail-mime-parser, pass a
[ZBateson\MailMimeParser\MailMimeParser](api/2.0/classes/ZBateson-MailMimeParser-MailMimeParser.html)
object as a dependency to your class, and call
[parse()](api/2.0/classes/ZBateson-MailMimeParser-MailMimeParser.html#method_parse).
The `parse()` method accepts a string, resource handle, or Psr7 StreamInterface
stream.

Alternatively for procedural/non dependency injected usage, calling
[Message::from()](api/2.0/classes/ZBateson-MailMimeParser-Message.html#method_from)
may be easier.  It accepts the same arguments as `parse()`.

```php
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

// $resource = fopen('my-file.mime', 'r');
// ...
$parser = new MailMimeParser();

// parse() returns an IMessage
$message = $parser->parse($resource, true);

// alternatively:
// $string = 'an email message to load';
$message = Message::from($string, false);
```

### Message headers

Headers are represented by
[ZBateson\MailMimeParser\Header\IHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-IHeader.html)
and sub-classes, depending on the type of header being parsed.  In general terms:

* [AddressHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-AddressHeader.html) is returned for headers consisting of addresses and address groups (e.g. `From:`, `To:`, `Cc:`, etc...)
* [DateHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-DateHeader.html) parses header values into a `DateTime` object (e.g. a `Date:` header)
* [ParameterHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-ParameterHeader.html) represents headers consisting of multiple name/values (e.g. `Content-Type:`)
* [IdHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-IdHeader.html) for ID headers, like `Message-ID`, `Content-ID`, `In-Reply-To` and `Reference`
* [ReceivedHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-ReceivedHeader.html) for `Received` header parsing
* [SubjectHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-SubjectHeader.html) for `Subject` headers (basically just mime-header decoding)
* [GenericHeader](api/2.0/classes/ZBateson-MailMimeParser-Header-GenericHeader.html) is used for any other header

To retrieve an IHeader object, call `IMessage::getHeader()` from a [ZBateson\MailMimeParser\IMessage](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html) object.

```php
// $message = $parser->parse($resource, true);
// ...

// getHeader('To') returns a ZBateson\MailMimeParser\Header\AddressHeader
$to = $message->getHeader('To');
if ($to->hasAddress('someone@example.com')) {
    // ...
}
// or to loop over AddressPart objects:
foreach ($to->getAddresses() as $address) {
    echo $address->getName() . ' ' . $address->getEmail();
}
```

For convenience, `IMessage::getHeaderValue()` can be used to retrieve the value of a header (for multi-part headers like email addresses, the first part's value is returned.  The value of an address is its email address, not a person's name if present).

```php
$contentType = $message->getHeaderValue('Content-Type');
```

In addition, `IMessage::getHeaderParameter()` can be used as a convenience method to retrieve the value of a parameter part of a `ParameterHeader`, for example:

```php
// 3rd argument optionally defines a default return value
$charset = $message->getHeaderParameter(
    'Content-Type',
    'charset',
    'us-ascii'
);
// as opposed to
$parameterHeader = $message->getHeader('Content-Type');

// 2nd argument to getValueFor also optional, defining a default return value
$charset = $parameterHeader->getValueFor('charset', 'us-ascii');
```

### Message parts (text, html and other attachments)

Essentially, the [\ZBateson\MailMimeParser\IMessage](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html) object returned is itself a sub-class of [\ZBateson\MailMimeParser\Message\Part\IMimePart](api/2.0/classes/ZBateson-MailMimeParser-Message.Part.IMimePart.html).  An IMessage can contain IMimePart children (which in turn could contain their own children).

Internally, IMessage maintains the structure of its parsed parts.  Most users will only be interested in text parts (plain or html) and attachments.  The following methods help you do just that:
* [IMessage::getTextStream()](api/2.0/classes/ZBateson-MailMimeParser-IMessMailMimeParserage.html#method_getTextStream)
* [IMessage::getTextContent()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextContent)
* [IMessage::getHtmlStream()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlStream)
* [IMessage::getHtmlContent()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlContent)
* [IMessage::getAttachmentPart()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getAttachmentPart)
* [IMessage::getAllAttachmentParts()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getAllAttachmentParts)

`IMessagePart` (the base class of all parts of a message) defines useful stream and content functions, e.g.:
* [IMessagePart::getContentStream()](api/2.0/classes/ZBateson-MailMimeParser-Message.Part.IMessagePart.html#method_getContentStream)
* [IMessagePart::getContentType()](api/2.0/classes/ZBateson-MailMimeParser-Message.Part.IMessagePart.html#method_getContentType)
* [IMessagePart::getFilename()](api/2.0/classes/ZBateson-MailMimeParser-Message.Part.IMessagePart.html#method_getFilename)
* [IMessagePart::getCharset()](api/2.0/classes/ZBateson-MailMimeParser-Message.Part.IMessagePart.html#method_getCharset)
* [IMessagePart::saveContent()](api/2.0/classes/ZBateson-MailMimeParser-Message.Part.IMessagePart.html#method_saveContent)

Example:
```php
// $message = $parser->parse($resource, true);
// ...
$att = $message->getAttachmentPart(0);
echo $att->getContentType();
echo $att->getContent();
```

Example writing files to disk:
```php
$atts = $message->getAllAttachmentParts();
foreach ($atts as $ind => $part) {
    $filename = $part->getHeaderParameter(
        'Content-Type',
        'name',
        $part->getHeaderParameter(
             'Content-Disposition',
             'filename',
             '__unknown_file_name_' . $ind
        )
    );

    $out = fopen('/path/to/dir/' . $filename, 'w');
    $str = $part->getBinaryContentResourceHandle();
    stream_copy_to_stream($str, $out);
    fclose($str);
    fclose($out);
}
```

### Reading text and html parts

As a convenient way of reading the text and HTML parts of an `IMessage`, use [IMessage::getTextStream()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextStream) and [IMessage::getHtmlStream()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlStream) or the shortcuts returning strings if you want strings directly [IMessage::getTextContent()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextContent) and [IMessage::getHtmlContent()](api/2.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlContent)

```php
// $message = $parser->parse($resource, true);
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

## API Documentation
* [Current (2.0)](api/2.0)
* [1.3](api/1.3)
* [1.2](api/1.2)
* [1.1](api/1.1)
* [1.0](api/1.0)
* [0.4](api/0.4)

## Contributors

Special thanks to our [contributors](https://github.com/zbateson/mail-mime-parser/graphs/contributors).
