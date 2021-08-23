[Home](/) - [Sponsors](/#sponsors) - [API Documentation](api/2.0) - [Upgrading to 2.0](upgrade-2.0) - [Contributors](/#contributors)

# Upgrading to 2.0

Most of the changes in 2.0 are internal and shouldn't affect users.  The main change is with how the parser works -- parsing on-demand as parts of a message are requested rather than parsing the whole message at once.

Most users will only need to change how the parser is called.  Whereas previously the stream would be read from and parsed immediately, now a reference to the resource is kept with the message so the on-demand parser can work.

Other changes include the return types for message parts (now using interfaces), header functions now return IHeader, part filtering now uses a callback, PartFilter provides some static functions that return filtering callbacks, and ParentPart and ParentHeaderPart have been removed (change in hierarchical structure.)

Lastly, the `\ZBateson\MailMimeParser\Message\Part` namespace no longer exists, and classes under it have been moved up a level:

- `ZBateson\MailMimeParser\Message\Part\MessagePart` -> `ZBateson\MailMimeParser\Message\MessagePart`
- `ZBateson\MailMimeParser\Message\Part\MimePart` -> `ZBateson\MailMimeParser\Message\MimePart`
- `ZBateson\MailMimeParser\Message\Part\ParentHeaderPart` -> `ZBateson\MailMimeParser\Message\MultiPart`
- `ZBateson\MailMimeParser\Message\Part\ParentPart` -> deleted
- `ZBateson\MailMimeParser\Message\Part\NonMimePart` -> `ZBateson\MailMimeParser\Message\NonMimePart`
- `ZBateson\MailMimeParser\Message\Part\UUEncodedPart` -> `ZBateson\MailMimeParser\Message\UUEncodedPart`

For a list of changes, [visit the 2.0 release page on github](https://github.com/zbateson/mail-mime-parser/releases/tag/2.0.0).

### Message::from() / MailMimeParser::parse()

An additional parameter needs to be passed to ``` Message::from() ``` and ``` MailMimeParser::parse() ``` specifying whether the passed resource should be 'attached' and closed when the returned IMessage object is destroyed, or kept open and closed manually after the message is parsed and the returned IMessage destroyed.

```php
// in 'attached' mode, the resource will be destroyed with the
// returned IMessage, so this is fine:
$message = Message::from(fopen('file.eml', 'r'), true);

// otherwise, if it's needed to stay open past IMessage, pass
// false as the second parameter
$resource = fopen('file.eml', 'r');
$message = Message::from($resource, false);
// read/use $message

fclose($resource); // only once $message is no longer needed
```

When passing a string resource, a Psr7 stream is created out of it.  Passing true/false as the second parameter
will have no effect.

### IMessage, IMimePart, IMultiPart, IUUEncodedPart, IMessagePart

These interfaces are used as return types instead of Message, MimePart, ParentHeaderPart, UUEncodedPart, and MessagePart.  Although those classes, with the exception of ParentHeaderPart (now MultiPart), still exist and are still the classes returned, they have been moved to a higher-level namespace (ZBateson\MailMimeParser\Message instead of ZBateson\MailMimeParser\Message\Part), and the interfaces are preferred because modules extending the parser's functionality may return different types.

### IHeader

Methods like `IMimePart::getHeader()` now return IHeader instead of AbstractHeader.  AbstractHeader is still used internally, but referring to it is discouraged as it doesn't need to be used by a class implementing IHeader and in the future this may cause problems.

### Part filtering

Part filtering is now done with callbacks instead of needing to create a sub-class of PartFilter, which works like array_filter.

```php
$parts = $message->getAllParts(function ($part) {
    // return true to include it in the returned array
    return ($part->hasContent());
});

```

