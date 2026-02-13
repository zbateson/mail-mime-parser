**[Home](/)** - [API Documentation](api/4.0) - [Upgrading to 4.0](upgrade-4.0)

Home: [About](#) - [Sponsors](#sponsors) - [Basics](#basicsintroduction) - [Usage Guide](#usage-guide) - [Contributors](#contributors)

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

## New in 4.0

Version 4.0 requires PHP 8.1+ and focuses on API cleanup and improved configurability.  For details, see the [4.0 Upgrade Guide](upgrade-4.0).

## Basics/Introduction

MailMimeParser has minimal external dependencies.  It requires mbstring to be
installed and configured, and works better with iconv being available as well.

It uses PHP-DI for dependency injection and guzzlehttp/psr7 for streams.  There
are two additional sister libraries that are used as well:
zbateson/stream-decorators which provides wrappers for psr7 streams, and
zbateson/mb-wrapper for charset conversion and some string manipulation routines.

```php
use ZBateson\MailMimeParser\Message;

$message = Message::from($handleOrStreamOrString, true);
$subject = $message->getSubject();
$text = $message->getTextContent();
$html = $message->getHtmlContent();
$from = $message->getHeader('From');
$fromName = $from->getPersonName();
$fromEmail = $from->getEmail();

$to = $message->getHeader('To');
// first email address can be accessed directly
$firstToName = $to->getPersonName();
$firstToEmail = $to->getEmail();

foreach ($to->getAddresses() as $addr) {
    $toName = $addr->getName();
    $toEmail = $addr->getEmail();
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
* Can handle multiple headers of the same name (unlike mailparse or any
  derivative).
* Parses parts of a message on-demand, so reading the subject line from a very
  large message can be very fast, or reading just the text body without the
  attachments is also very fast.
* Can edit a message by setting headers, overwriting content, changing to/from
  multipart/alternative or mixed, add/remove attachments, set the message as
  multipart/signed (signing functionality not included... the library can
  convert the message and provide the part of the message that needs to be
  signed).
* Uses streams internally to avoid keeping everything in memory.
* PSR-compliant, unit and functionally tested.

## Usage Guide

### Previous versions

> The usage guide below applies to 2.0+. For older versions:
>
> For the 0.4 usage guide, [click here](usage-guide-0.4.html)
> For the 1.0 usage guide, [click here](usage-guide-1.0.html)

### Parsing an email

To parse an email using zbateson/mail-mime-parser, pass a
[ZBateson\MailMimeParser\MailMimeParser](api/4.0/classes/ZBateson-MailMimeParser-MailMimeParser.html)
object as a dependency to your class, and call
[parse()](api/4.0/classes/ZBateson-MailMimeParser-MailMimeParser.html#method_parse).
The `parse()` method accepts a string, resource handle, or Psr7 StreamInterface
stream.

Alternatively for procedural/non dependency injected usage, calling
[Message::from()](api/4.0/classes/ZBateson-MailMimeParser-Message.html#method_from)
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
[ZBateson\MailMimeParser\Header\IHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-IHeader.html)
and sub-classes, depending on the type of header being parsed.  In general terms:

* [AddressHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-AddressHeader.html) is returned for headers consisting of addresses and address groups (e.g. `From:`, `To:`, `Cc:`, etc...)
* [DateHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-DateHeader.html) parses header values into a `DateTime` object (e.g. a `Date:` header)
* [ParameterHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-ParameterHeader.html) represents headers consisting of multiple name/values (e.g. `Content-Type:`)
* [IdHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-IdHeader.html) for ID headers, like `Message-ID`, `Content-ID`, `In-Reply-To` and `Reference`
* [ReceivedHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-ReceivedHeader.html) for `Received` header parsing
* [SubjectHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-SubjectHeader.html) for `Subject` headers (basically just mime-header decoding)
* [GenericHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-GenericHeader.html) is used for any other header

To retrieve an IHeader object, call `IMessage::getHeader()` from a [ZBateson\MailMimeParser\IMessage](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html) object.

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
    echo $address->getPersonName() . ' ' . $address->getEmail();
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

Essentially, the [\ZBateson\MailMimeParser\IMessage](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html) object returned is itself a sub-class of [\ZBateson\MailMimeParser\Message\IMimePart](api/4.0/classes/ZBateson-MailMimeParser-Message-IMimePart.html).  An IMessage can contain IMimePart children (which in turn could contain their own children).

Internally, IMessage maintains the structure of its parsed parts.  Most users will only be interested in text parts (plain or html) and attachments.  The following methods help you do just that:
* [IMessage::getTextStream()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextStream)
* [IMessage::getTextContent()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextContent)
* [IMessage::getHtmlStream()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlStream)
* [IMessage::getHtmlContent()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlContent)
* [IMessage::getAttachmentPart()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getAttachmentPart)
* [IMessage::getAllAttachmentParts()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getAllAttachmentParts)

`IMessagePart` (the base class of all parts of a message) defines useful stream and content functions, e.g.:
* [IMessagePart::getContentStream()](api/4.0/classes/ZBateson-MailMimeParser-Message-IMessagePart.html#method_getContentStream)
* [IMessagePart::getContentType()](api/4.0/classes/ZBateson-MailMimeParser-Message-IMessagePart.html#method_getContentType)
* [IMessagePart::getFilename()](api/4.0/classes/ZBateson-MailMimeParser-Message-IMessagePart.html#method_getFilename)
* [IMessagePart::getCharset()](api/4.0/classes/ZBateson-MailMimeParser-Message-IMessagePart.html#method_getCharset)
* [IMessagePart::saveContent()](api/4.0/classes/ZBateson-MailMimeParser-Message-IMessagePart.html#method_saveContent)

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

As a convenient way of reading the text and HTML parts of an `IMessage`, use [IMessage::getTextStream()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextStream) and [IMessage::getHtmlStream()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlStream) or the shortcuts returning strings if you want strings directly [IMessage::getTextContent()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getTextContent) and [IMessage::getHtmlContent()](api/4.0/classes/ZBateson-MailMimeParser-IMessage.html#method_getHtmlContent)

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

### Encryption and signing

Optional companion packages add S/MIME and PGP/MIME support.  Install one (or both) via composer:

```
composer require zbateson/mmp-crypt-smime
composer require zbateson/mmp-crypt-gpg
```

* [zbateson/mmp-crypt-smime](https://github.com/zbateson/mmp-crypt-smime) -- S/MIME via PHP's OpenSSL extension
* [zbateson/mmp-crypt-gpg](https://github.com/zbateson/mmp-crypt-gpg) -- PGP/MIME via PEAR's Crypt_GPG

Once installed, encrypted and signed messages are automatically detected during parsing.  No extra configuration is needed for detection -- but decryption requires providing credentials.

#### Reading encrypted messages (S/MIME)

```php
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MmpCrypt\EncryptedParserService;
use ZBateson\MmpCryptSmime\SMimeOpenSsl;

// Configure the S/MIME decryption credentials
$smime = new SMimeOpenSsl(
    decryptionCredentials: [
        ['cert' => file_get_contents('cert.pem'), 'key' => file_get_contents('key.pem')],
    ]
);

// Register the configured instance before parsing
$container = MailMimeParser::getGlobalContainer();
$container->get(EncryptedParserService::class)->addCryptImplementation($smime);

$message = Message::from($encryptedEmail, false);

// getStream() returns the decrypted content by default
echo $message->getTextContent();

// access the original encrypted stream if needed
$encrypted = $message->getEncryptedStream();
```

#### Reading encrypted messages (PGP/MIME)

PGP/MIME requires the `gpg` binary and a configured GnuPG keyring with the appropriate keys imported.  See the [GnuPG documentation](https://gnupg.org/documentation/) for details on setting up your keyring.

```php
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MmpCrypt\EncryptedParserService;
use ZBateson\MmpCryptGpg\GpgPear;

$cryptGpg = new \Crypt_GPG();
$cryptGpg->addDecryptKey('recipient@example.com', 'passphrase');

$crypt = new GpgPear($cryptGpg);

// Alternative if proc_open is restricted in your environment.
// Operates on strings, so limited to smaller messages.
// $gpg = new \GnuPG();
// $gpg->adddecryptkey('recipient@example.com', 'passphrase');
// $crypt = new \ZBateson\MmpCryptGpg\GpgPhp($gpg);

$container = MailMimeParser::getGlobalContainer();
$container->get(EncryptedParserService::class)->addCryptImplementation($crypt);

$message = Message::from($pgpEncryptedEmail, false);
echo $message->getTextContent();
```

#### Composing encrypted and signed messages (S/MIME)

```php
use ZBateson\MailMimeParser\Message;
use ZBateson\MmpCryptSmime\SMimeMessage;
use ZBateson\MmpCryptSmime\SMimeOpenSsl;

$message = Message::from("Content-Type: text/plain\r\nSubject: Secret\r\n\r\nHello!", false);

// Encrypt for a recipient
$crypt = new SMimeOpenSsl(recipientCerts: file_get_contents('recipient-cert.pem'));
$encrypted = SMimeMessage::encrypt($message, $crypt);

// Sign a message
$crypt = new SMimeOpenSsl(
    signerCert: file_get_contents('signer-cert.pem'),
    signerKey: file_get_contents('signer-key.pem'),
);
$signed = SMimeMessage::sign($message, $crypt);

// Sign then encrypt (recommended per RFC 2634)
$crypt = new SMimeOpenSsl(
    recipientCerts: file_get_contents('recipient-cert.pem'),
    signerCert: file_get_contents('signer-cert.pem'),
    signerKey: file_get_contents('signer-key.pem'),
);
$signedAndEncrypted = SMimeMessage::signAndEncrypt($message, $crypt);

echo (string) $signedAndEncrypted;
```

#### Composing encrypted and signed messages (PGP/MIME)

```php
use ZBateson\MailMimeParser\Message;
use ZBateson\MmpCryptGpg\GpgPear;
use ZBateson\MmpCryptGpg\PgpMimeMessage;

$message = Message::from("Content-Type: text/plain\r\nSubject: Secret\r\n\r\nHello!", false);

$cryptGpg = new \Crypt_GPG();
$cryptGpg->addEncryptKey('recipient@example.com');
$cryptGpg->addSignKey('sender@example.com', 'passphrase');
$crypt = new GpgPear($cryptGpg);

// Alternative if proc_open is restricted in your environment.
// Operates on strings, so limited to smaller messages.
// $gpg = new \GnuPG();
// $gpg->addencryptkey('recipient@example.com');
// $gpg->addsignkey('sender@example.com', 'passphrase');
// $crypt = new \ZBateson\MmpCryptGpg\GpgPhp($gpg);

// Encrypt
$encrypted = PgpMimeMessage::encrypt($message, $crypt);

// Sign
$signed = PgpMimeMessage::sign($message, $crypt);

// Sign then encrypt
$signedAndEncrypted = PgpMimeMessage::signAndEncrypt($message, $crypt);

echo (string) $signedAndEncrypted;
```

### Error reporting and logging

Some basic logging has been added, and a logger can be provided either globally
or to an instance of MailMimeParser.

```php
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\IMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('mail-parser');
$logger->pushHandler(new StreamHandler(__DIR__ . '/mmp.log', 'debug'));

// set it globally, calling new Message() would use the 'globally-provided'
// LoggerInterface
MailMimeParser::setGlobalLogger($logger);

// or set it on an instance, any instances created through this via parsing,
// etc... would use the provided logger, but calling 'new Message()' yourself
// would require passing it manually
$parser = new MailMimeParser($logger);

$message = Message::from($string, false);
```

[IMessagePart](api/4.0/classes/ZBateson-MailMimeParser-Message-IMessagePart.html) (and therefore all interfaces that inherit from it, IMimePart, IMessage),
[IHeader](api/4.0/classes/ZBateson-MailMimeParser-Header-IHeader.html), and
[IHeaderPart](api/4.0/classes/ZBateson-MailMimeParser-Header-IHeaderPart.html) all inherit from
[IErrorBag](api/4.0/classes/ZBateson-MailMimeParser-IErrorBag.html).  Errors that occur on any child can be inspected at the top-level IMessage with a call to
[IErrorBag::getAllErrors()](api/4.0/classes/ZBateson-MailMimeParser-IErrorBag.html#method_getAllErrors) which will return all
[Error](api/4.0/classes/ZBateson-MailMimeParser-Error.html) objects that have occurred.  Optionally passing 'true' to validate getAllErrors may perform additional
validation on objects, and a PSR level can be provided to retrieve objects logged at different levels.  The Error class has a 'getObject()' method to retrieve the
object the error occurred on (helpful if calling getAllErrors at a top-level, and you want to know which object it actually occurred on).

## API Documentation
* [Current (4.0)](api/4.0)
* [3.0](api/3.0)
* [2.4](api/2.4)
* [2.3](api/2.3)
* [2.2](api/2.2)
* [2.1](api/2.1)
* [2.0](api/2.0)
* [1.3](api/1.3)
* [1.2](api/1.2)
* [1.1](api/1.1)
* [1.0](api/1.0)
* [0.4](api/0.4)

## Contributors

Special thanks to our [contributors](https://github.com/zbateson/mail-mime-parser/graphs/contributors).
