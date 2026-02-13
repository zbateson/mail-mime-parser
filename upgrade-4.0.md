**[Home](/)** - [API Documentation](api/4.0) - [Upgrading to 4.0](upgrade-4.0)

# Upgrading to 4.0

Changes in 4.0 focus on API cleanup, stricter typing, and improved
configurability.  The minimum PHP version is now 8.1.  Basic usage of parsing
messages and reading content remains unchanged, but several interface signatures
have been updated for consistency and correctness.

## PHP 8.1 Requirement

The minimum required PHP version has been bumped from 8.0 to 8.1.

## Breaking Changes

### Method return type changes

* `IMultiPart::removePart()` now returns `static` instead of `?int`.  Code that
  previously used the returned index to determine the position of the removed
  part should use `array_search()` on `getChildParts()` before removing instead.

* `IMessage::removeTextPart()`, `removeAllTextParts()`, `removeHtmlPart()`, and
  `removeAllHtmlParts()` now return `static` instead of `bool`.  This enables
  fluent method chaining.  If you relied on the boolean return value to check
  whether a part was removed, check with the appropriate `has`/`get` method
  instead.

* `IMessagePart::getContentType()` now returns `string` instead of `?string`.
  It will never return null — when no Content-Type header exists, the default
  value is returned (e.g. `'text/plain'` for MIME parts).

### Interface changes

* `IMessagePart` no longer extends `SplSubject`.  The `SplSubject` interface is
  now implemented directly on `MessagePart`.  This was an internal implementation
  detail that should not have been part of the public interface.  If you were
  calling `attach()`, `detach()`, or `notify()` on an `IMessagePart`, cast or
  type-hint against `MessagePart` instead.

* `IHeader` now requires a `getDecodedValue(): string` method.  This returns the
  full decoded and unfolded value of the header reconstructed from parsed parts.
  Unlike `getValue()` which returns only the first part's value, this returns the
  complete representation.  For example:
  - An `AddressHeader` with `=?UTF-8?Q?J=C3=B6hn?= <john@example.com>` returns
    `Jöhn <john@example.com>`
  - A `ParameterHeader` for `Content-Type: text/html; charset=utf-8` returns
    `text/html; charset=utf-8`
  - A `GenericHeader` or `SubjectHeader` returns the concatenated decoded value
    of all non-comment parts

  All built-in header classes implement this method.  If you have custom
  `IHeader` implementations, you will need to add this method.

### Class changes

* `PartFilter` is now a `final` class with a private constructor.  It was
  previously `abstract`.  This should only affect code that was extending
  `PartFilter`, which was not an intended use case.  The static factory methods
  `fromContentType()`, `fromInlineContentType()`, and `fromDisposition()` remain
  unchanged.

### Parameter renames

* `Message::from()`: the `$attached` parameter has been renamed to `$autoClose`
  to better describe its purpose.  If you were passing this argument by name
  (named arguments), update the name accordingly.

* `MailMimeParser::parse()`: the `$attached` parameter has been renamed to
  `$autoClose` for the same reason.

### Stream layer

* `PartStreamContainer::setStream()`, `getStream()`, `getContentStream()`, and
  `getBinaryContentStream()` now accept and return `StreamInterface` instead of
  `MessagePartStreamDecorator`.  This also applies to `ParserPartStreamContainer`
  and `StreamFactory::newMessagePartStream()` / `newDecoratedMessagePartStream()`.

  If you were type-hinting against `MessagePartStreamDecorator`, update to
  `StreamInterface`.

## New Features

### Configurable fallback charset

Text parts without a declared charset default to `ISO-8859-1` per RFC 2045.
Many modern messages omit the charset and are actually UTF-8.  You can now
configure the fallback globally:

```php
MailMimeParser::setFallbackCharset('UTF-8');
```

Or via the DI configuration by overriding the `'defaultFallbackCharset'` entry.
The fallback charset is injected through proper dependency injection into
`MimePart`, `NonMimePart`, and their factories.

### IHeader::getDecodedValue()

A new method on all header objects that returns the full decoded value
reconstructed from parsed parts (excluding comments).  See the interface changes
section above for details.

### PHPDoc return type hints on getHeader()

`IMimePart::getHeader()` now includes PHPDoc documentation listing the concrete
header types that may be returned (`AddressHeader`, `DateHeader`,
`GenericHeader`, `IdHeader`, `ParameterHeader`, `ReceivedHeader`,
`SubjectHeader`).  This improves IDE autocompletion and static analysis support.

## Bug Fixes

* `Message::getErrorLoggingContextName()` was incorrectly calling
  `getContentId()` instead of `getMessageId()`.  This has been corrected.
