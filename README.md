# zbateson/mail-mime-parser

Standalone, testable and PSR-compliant mail mime parser alternative to PHP's imap* functions and pear libraries for reading messages in _Internet Message Format_ (RFC-5322, RFC-2822 and RFC-822).

[![Build Status](https://travis-ci.org/zbateson/MailMimeParser.svg?branch=master)](https://travis-ci.org/zbateson/MailMimeParser) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zbateson/MailMimeParser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zbateson/MailMimeParser/?branch=master)

The goals of this project are to be:

* Well written
* Standards-compliant but forgiving
* Includable via composer
* Tested where possible

To include it for use in your project, please install via composer:

```
composer require zbateson/mail-mime-parser
```

## Requirements

MailMimeParser requires PHP 5.4 or newer.  Tested on PHP 5.4, 5.5, 5.6 and 7.  HHVM is not currently supported.

## Usage

```php
$mailParser = new ZBateson\MailMimeParser();

$handle = fopen('file.mime', 'r');
$message = $mailParser->parse($handle);         // returns a ZBateson\MailMimeParser\Message
fclose($handle);

echo $message->getHeaderValue('from');          // user@example.com
echo $message->getHeader('from')->getName();    // Person Name
echo $message->getHeaderValue('subject');       // The email's subject

$res = $message->getTextStream();               // or getHtmlStream
echo stream_get_contents($res);

$att = $message->getAttachmentPart(0);          // first attachment
echo $att->getHeaderValue('Content-Type');      // text/plain for instance
echo $att->getHeaderParameter(                  // value of "charset" part
    'content-type',
    'charset'
);
echo stream_get_contents(
    $att->getContentRersourceHandle()
);
```

## Documentation

* [Usage Guide](https://github.com/zbateson/MailMimeParser/wiki/Usage-Guide)
* [API Reference](https://github.com/zbateson/MailMimeParser/wiki/ApiIndex)

## License

BSD licensed - please see [license agreement](https://github.com/zbateson/MailMimeParser/blob/master/LICENSE).
