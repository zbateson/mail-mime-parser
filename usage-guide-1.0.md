[Home](/) - [Sponsors](/#sponsors) - [API Documentation](api/2.1) - [Upgrading to 2.0](upgrade-2.0) - [Contributors](/#contributors)

## Deprecation Notice (since 1.2.1)

`getContentResourceHandle`, `getTextResourceHandle`, and `getHtmlResourceHandle` have all been deprecated due to [#106](https://github.com/zbateson/mail-mime-parser/issues/106). fread() will only return a single byte of a multibyte char, and so will cause potentially unexpected results/warnings in some cases, and psr7 streams should be used instead.  Note that this deprecation doesn't apply to `getBinaryContentResourceHandle` or `getResourceHandle`.

## Introduction

MailMimeParser has minimal external dependencies.  It requires mbstring to be
installed and configured, and two additional composer dependencies that are
downloaded and installed via composer: guzzle's guzzlehttp\psr7 library, and a
sister library created to house psr7 stream decorators used by MailMimeParser
called zbateson\stream-decorators.

Yes, it's *this* easy:

```php
use ZBateson\MailMimeParser\Message;
use GuzzleHttp\Psr7;

$message = Message::parse($handleOrStreamOrString);
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
* Can edit a message by setting headers, overwriting content, changing to/from
  multipart/alternative or mixed, add/remove attachments, set the message as
  multipart/signed (signing functionality not included... the library can
  convert the message and provide the part of the message that needs to be
  signed).
* Uses streams internally to avoid keeping everything in memory.
* PSR-compliant, unit and functionally tested.

### Parsing an email

To parse an email using zbateson/mail-mime-parser, pass a
[ZBateson\MailMimeParser\MailMimeParser](api/1.3/classes/ZBateson-MailMimeParser-MailMimeParser.html)
object as a dependency to your class, and call
[parse()](api/1.3/classes/ZBateson-MailMimeParser-MailMimeParser.html#method_parse).
The `parse()` method accepts a string, resource handle, or Psr7 StreamInterface
stream.

Alternatively for procedural/non dependency injected usage, calling
[Message::from()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_from)
may be easier.  It accepts the same arguments as `parse()`.

```php
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

// $resource = fopen('my-file.mime', 'r');
// ...
$parser = new MailMimeParser();

// parse() returns a Message
$message = $parser->parse($resource);

// alternatively:
// $string = 'an email message to load';
$message = Message::from($string);
```

### Message headers

Headers are represented by
[ZBateson\MailMimeParser\Header\AbstractHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-AbstractHeader.html)
and sub-classes, depending on the type of header being parsed.  In general terms:

* [AddressHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-AddressHeader.html) is returned for headers consisting of addresses and address groups (e.g. `From:`, `To:`, `Cc:`, etc...)
* [DateHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-DateHeader.html) parses header values into a `DateTime` object (e.g. a `Date:` header)
* [ParameterHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-ParameterHeader.html) represents headers consisting of multiple name/values (e.g. `Content-Type:`)
* [IdHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-IdHeader.html) for ID headers, like 'Message-ID', 'Content-ID', 'In-Reply-To' and 'Reference'
* [ReceivedHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-ReceivedHeader.html) for 'Received' header parsing
* [GenericHeader](api/1.3/classes/ZBateson-MailMimeParser-Header-GenericHeader.html) is used for any other header

To retrieve an AbstractHeader object, call `Message::getHeader()` from a [ZBateson\MailMimeParser\Message](api/1.3/classes/ZBateson-MailMimeParser-Message.html) object.

```php
// $message = $parser->parse($resource);
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

For convenience, `Message::getHeaderValue()` can be used to retrieve the value of a header (for multi-part headers like email addresses, the first part's value is returned.  The value of an address is its email address, not a person's name if present).

```php
$contentType = $message->getHeaderValue('Content-Type');
```

In addition, `Message::getHeaderParameter()` can be used as a convenience method to retrieve the value of a parameter part of a `ParameterHeader`, for example:

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

Essentially, the [\ZBateson\MailMimeParser\Message](api/1.3/classes/ZBateson-MailMimeParser-Message.html) object returned is itself a sub-class of [\ZBateson\MailMimeParser\Message\Part\MimePart](api/1.3/classes/ZBateson-MailMimeParser-Message.Part.MimePart.html).  The difference between them is: MimeParts can only be added to a Message.

Internally, a Message maintains the structure of its parsed parts.  Most users will only be interested in text parts (plain or html) and attachments.  The following methods help you do just that:
* [Message::getTextStream()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getTextStream)
* [Message::getTextContent()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getTextContent)
* [Message::getHtmlStream()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getHtmlStream)
* [Message::getHtmlContent()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getHtmlContent)
* [Message::getAttachmentPart()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getAttachmentPart)
* [Message::getAllAttachmentParts()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getAllAttachmentParts)

`MessagePart` (returned by `Message::getAttachmentPart()`) defines useful stream and content functions, e.g.:
* [MessagePart::getContentStream()](api/1.3/classes/ZBateson-MailMimeParser-Message.Part.MessagePart.html#method_getContentStream)
* [MessagePart::getContentType()](api/1.3/classes/ZBateson-MailMimeParser-Message.Part.MessagePart.html#method_getContentType)
* [MessagePart::getFilename()](api/1.3/classes/ZBateson-MailMimeParser-Message.Part.MessagePart.html#method_getFilename)
* [MessagePart::getCharset()](api/1.3/classes/ZBateson-MailMimeParser-Message.Part.MessagePart.html#method_getCharset)
* [MessagePart::saveContent()](api/1.3/classes/ZBateson-MailMimeParser-Message.Part.MessagePart.html#method_saveContent)

Example:
```php
// $message = $parser->parse($resource);
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

As a convenient way of reading the text and HTML parts of a `Message`, use [Message::getTextStream()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getTextStream) and [Message::getHtmlStream()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getHtmlStream) or the shortcuts returning strings if you want strings directly [Message::getTextContent()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getTextContent) and [Message::getHtmlContent()](api/1.3/classes/ZBateson-MailMimeParser-Message.html#method_getHtmlContent)

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

## API Documentation
* [Current (1.3)](api/1.3)
* [1.2](api/1.2)
* [1.1](api/1.1)
* [1.0](api/1.0)
* [0.4](api/0.4)

## Contributors

Special thanks to our [contributors](https://github.com/zbateson/MailMimeParser/graphs/contributors).
