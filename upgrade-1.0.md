[Home](/) - [Sponsors](/#sponsors) - [API Documentation](api/3.0) - [Upgrading to 3.0](upgrade-3.0) - [Contributors](/#contributors)

# Upgrading to 1.0

The majority of changes won't have an effect on simple implementations parsing emails to extract content/attachments.  For simple uses, only a few changes will need to be made -- most likely changing calls from ``` Message::getTextStream ``` and ``` Message::getHtmlStream ``` to ``` Message::getTextResourceHandle ``` and ``` Message::getHtmlResourceHandle ```.

If you're modifying messages after parsing them, ``` MimePart::attachContentResourceHandle ``` has been removed.  Instead, ``` MessagePart::setContent ``` accepts a wider range of parameters.  You can pass a resource handle, a string or a Psr7 StreamInterface.

In addition, if you're signing or verifying messages, the method signature for that changed as well and would need to be updated (see below under the Message class).

The class structure of messages changed, more complicated use cases may require a more careful upgrade, and a further look at the new inheritance structure.

## Message class

The most common change users will run into is the change from resource streams to Psr7 Streams.  The following method signatures have changed:

 * ``` Message::getTextStream ``` and ``` Message::getHtmlStream ``` now return Psr7 Streams.  To get a resource handle, call ``` Message::getTextResourceHandle ``` or ``` Message::getHtmlResourceHandle  ``` instead, or use the Psr7 stream.

```php
$message = Message::from($handle);
echo $message->getTextStream()->getContents();
// equivalent shortcut
echo $message->getTextContent();
// or if your code is already using a resource handle, it may be easier to
// use getTextResourceHandle
$contentHandle = $message->getTextResourceHandle
```

The way a message is written out has been changed:

 * Psr7 streams are used for writing as well instead of resource handles and stream filters
 * The original handle used for parsing isn't necessarily kept.  Instead, write operations trigger the current stream to be set to null.  When a stream is requested, if the part's stream is null a Stream\MessagePartStream is returned (which in turn writes the message and it's children recursively).
 * To that end, ``` Message::getOriginalStreamHandle ``` no longer exists, but ``` Message::getResourceHandle ``` and ``` Message::getStream ``` were introduced instead.

Methods related to signing and verification changed names:

 * ``` Message::getSignableBody ``` and the mouthy ``` Message::getOriginalMessageStringForSignatureVerification ``` became ``` Message::getSignedMessageAsString ``` -- because in the new version of the library an unchanged message doesn't get rewritten, the same function can be used for verification as well as signing.
 * ``` Message::createSignaturePart ``` became ``` Message::setSignature ``` -- if the part doesn't exist it's created, but if not, the existing part is used.

## Message part classes

MimePart used to be the base class for Message and other parts: NonMimePart and UUEncodedPart.  Now, a new base class was created, and the structure has changed:

 * MessagePart -- abstract base class, contains stream/content methods, can hold a parent, and defines abstract methods: getContentType, getCharset, getContentDisposition, getContentTransferEncoding, isTextPart, isMime, getFilename
 * * NonMimePart -- represents a text part for a non-mime message
 * * * UUEncodedPart -- a uuencoded attachment part for a non-mime message
 * * ParentPart -- abstract MessagePart containing children and providing methods to retrieve/filter/set them, e.g. getChild, getChildParts, getPart, getAllParts, getPartByMimeType, addChild, removeChild, etc...
 * * * ParentHeaderPart -- abstract ParentPart with headers, provides: getHeader, getRawHeaders, getHeaderValue, getHeaderParameter, setRawHeader, removeHeader
 * * * * MimePart concrete ParentHeaderPart, uses headers to implement abstract MessagePart
 * * * * * Message - a MimePart with added routines to determine if it's a Mime message, retrieve/set attachments, and get or manipulate content.

As mentioned in the introduction, ``` MimePart::attachContentResourceHandle ``` has been removed.  Instead, ``` MessagePart::setContent ``` accepts a wider range of parameters.  You can pass a resource handle, a string or a Psr7 StreamInterface.

``` MimePart::detachContentResourceHandle ``` is now ``` MessagePart::detachContentStream ```.

