# zbateson/mail-mime-parser

Standalone, testable and PSR-compliant mail mime parser alternative to PHP's imap* functions and pear libraries for reading messages in _Internet Message Format_ (RFC-5322, RFC-2822 and RFC-822).

[![Build Status](https://travis-ci.org/zbateson/MailMimeParser.svg?branch=master)](https://travis-ci.org/zbateson/MailMimeParser) [![Code Coverage](https://scrutinizer-ci.com/g/zbateson/MailMimeParser/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/zbateson/MailMimeParser/?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zbateson/MailMimeParser/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zbateson/MailMimeParser/?branch=master)
[![Total Downloads](https://poser.pugx.org/zbateson/mail-mime-parser/downloads)](https://packagist.org/packages/zbateson/mail-mime-parser)
[![Latest Stable Version](https://poser.pugx.org/zbateson/mail-mime-parser/version)](https://packagist.org/packages/zbateson/mail-mime-parser)

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

MailMimeParser requires PHP 5.4 or newer or HHVM.  Tested on PHP 5.4, 5.5, 5.6 and 7 and HHVM 3.4.

## Usage

```php
$mailParser = new ZBateson\MailMimeParser\MailMimeParser();

$handle = fopen('file.mime', 'r');
$message = $mailParser->parse($handle);         // returns a ZBateson\MailMimeParser\Message
fclose($handle);

echo $message->getHeaderValue('from');          // user@example.com
echo $message
    ->getHeader('from')
    ->getPersonName();                          // Person Name
echo $message->getHeaderValue('subject');       // The email's subject

echo $message->getTextContent();                // or getHtmlContent

$att = $message->getAttachmentPart(0);          // first attachment
echo $att->getHeaderValue('Content-Type');      // text/plain for instance
echo $att->getHeaderParameter(                  // value of "charset" part
    'content-type',
    'charset'
);
echo stream_get_contents(
    $att->getContentResourceHandle()
);
```

## Documentation

* [Wiki Introduction](https://github.com/zbateson/MailMimeParser/wiki)
* [Usage Guide](https://github.com/zbateson/MailMimeParser/wiki/Usage-Guide)
* [API Reference](https://github.com/zbateson/MailMimeParser/wiki/ApiIndex)

## License

BSD licensed - please see [license agreement](https://github.com/zbateson/MailMimeParser/blob/master/LICENSE).
