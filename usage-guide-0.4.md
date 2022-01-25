[Home](/) - [Sponsors](/#sponsors) - [API Documentation](api/2.2) - [Upgrading to 2.0](upgrade-2.0) - [Contributors](/#contributors)

### Parsing a stream

To parse a mime stream using zbateson/mail-mime-parser, create a [ZBateson\MailMimeParser\MailMimeParser](api/0.4/classes/ZBateson-MailMimeParser-MailMimeParser.html) object and call `parse()`, passing it a resource handle or string. The `MailMimeParser::parse()` method returns a [ZBateson\MailMimeParser\Message](api/0.4/classes/ZBateson-MailMimeParser-Message.html) object representing the parsed mime message.

```php
// $resource = fopen('my-file.mime', 'r');
// ...
$parser = new \ZBateson\MailMimeParser\MailMimeParser();
$message = $parser->parse($resource);     // returns a ZBateson\MailMimeParser\Message
```

### Message headers

Headers are represented by [ZBateson\MailMimeParser\Header\AbstractHeader](api/0.4/classes/ZBateson-MailMimeParser-Header.AbstractHeader.html) and sub-classes depending on the type of header.  In general:

* [AddressHeader](api/0.4/classes/ZBateson-MailMimeParser-Header.AddressHeader.html) is returned for headers consisting of addresses and address groups (e.g. `From:`, `To:`, `Cc:`, etc...)
* [DateHeader](api/0.4/classes/ZBateson-MailMimeParser-Header.DateHeader.html) parses header values into a `DateTime` object (e.g. a `Date:` header)
* [ParameterHeader](api/0.4/classes/ZBateson-MailMimeParser-Header.ParameterHeader.html) represents headers consisting of multiple name/values (e.g. `Content-Type:`)
* [GenericHeader](api/0.4/classes/ZBateson-MailMimeParser-Header.GenericHeader.html) is used for any other header

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

In addtion, `Message::getHeaderParameter()` can be used as a convenience method to retrieve the value of parameter part of a `ParameterHeader`, for example:

```php
// 3rd parameter optionally defines a default return value
$charset = $message->getHeaderParameter('Content-Type', 'charset', 'us-ascii');
// as opposed to
$parameterHeader = $message->getHeader('Content-Type');
$charset = $parameterHeader->getValueFor('charset', 'us-ascii');    // 2nd parameter also optional
```

### Mime message parts (text, html and other attachments)

Essentially, the `\ZBateson\MailMimeParser\Message` object returned is itself a sub-class of `\ZBateson\MailMimeParser\MimePart`.  The difference between them is: MimeParts can only be added to a Message.

All parsed mime parts, and deeper mime parts, are added to the Message as attachment parts for convenience, rather than mimic the original structure of the mime-formatted message however it's sent.

The Message provides convenience methods for accessing attachment parts, and the main message body (text and/or HTML):
* [Message::getTextPart()](api/0.4/classes/ZBateson-MailMimeParser-Message.html#method_getTextPart)
* [Message::getHtmlPart()](api/0.4/classes/ZBateson-MailMimeParser-Message.html#method_getHtmlPart)
* [Message::getAttachmentPart()](api/0.4/classes/ZBateson-MailMimeParser-Message.html#method_getAttachmentPart)
* [Message::getAllAttachmentParts()](api/0.4/classes/ZBateson-MailMimeParser-Message.html#method_getAllattachmentParts)

The MimePart defines header functions and stream functions, e.g.
* [MimePart::getContentResourceHandle()](ZBateson-MailMimeParser-MimePart#method_getContentResourceHandle)
* [MimePart::getHeader()](ZBateson-MailMimeParser-MimePart#method_getHeader)
* [MimePart::getHeaderValue()](ZBateson-MailMimeParser-MimePart#method_getHeaderValue)
* [MimePart::getHeaderParameter()](ZBateson-MailMimeParser-MimePart#method_getHeaderParameter)

```php
// $message = $parser->parse($resource);
// ...
$att = $message->getAttachmentPart(0);
echo $att->getHeader('Content-Type');
echo stream_get_contents($att->getContentResourceHandle());
```

### Reading text and html parts

As a convenient way of reading the text and HTML parts of a Message, use [Message::getTextStream()](api/0.4/classes/ZBateson-MailMimeParser-Message.html#method_getTextStream) and [Message::getHtmlStream()](api/0.4/classes/ZBateson-MailMimeParser-Message.html#method_getHtmlStream).

```php
// $message = $parser->parse($resource);
// ...
$txtHandle = $message->getTextStream();
echo stream_get_contents($txtHandle);
$htmlHandle = $message->getHtmlStream();
echo stream_get_contents($htmlHandle);
```

