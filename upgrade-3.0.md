**[Home](/)** - [API Documentation](api/3.0) - [Upgrading to 4.0](upgrade-4.0)

# Upgrading to 3.0

Changes in 3.0 concentrate mostly on additions to error reporting, logging,
updating the dependency injection library used, and migrating to php8+.  Basic
usage hasn't changed, but more advanced header inspection has: specifically
changes to HeaderPart classes, GenericHeader::getValue has changed to return the
concatenated value of all child parts, rather than the value of the first part
(which only applies to any header that doesn't have a more specialized header
type -- so not an address, date, id, parameter, received or subject header).

For header parts, please inspect the documentation for them when upgrading,
there are many changes there, most of which are structural to support error
reporting:

* Creating a HeaderPart out of tokens is now done using child header parts
  so errors can be kept against the specific HeaderPart it was found on.
* There is no longer a LiteralPart, instead there is a ContainerPart which
  serves as a container for other parts, allowing a full introspection of errors
  that occur on it or any child parts (extends the new IErrorBag and its
  getAllErrors which returns errors for the current IErrorBag and all children)
* There is no longer a MimeLiteralPart, instead there is a MimeToken which
  represents a single mime header token.
* Comments are generally parsed into a HeaderPart, so for instance in a
  parameter header, Content-Type: text/html; (a comment)name=value, the comment
  is part of the "name=value" parameter part, and further part of the "name"
  part.
* Created IHeader::getAllParts which returns all parts and which may include
  comment parts (although in most cases those comment parts are part of other
  ContainerParts as mentioned).  IHeader::getParts stays the same although the
  number and types of parts returned may be different.

Here's a detailed list of changes:

* Logging support, pass in a LoggerInterface to the constructor or call
  MailMimeParser::setGlobalLogger.  Not much is actually logged at this point
  (please submit pull requests to add useful log messages)
  
* ErrorBag class -- most user classes extend ErrorBag which allows classes to
  keep track of errors by calling addError, and users to call getErrors or
  getAllErrors (to include errors in child classes).
  
  Additional validation on objects is not performed, but can be done by passing
  'true' as the validate parameter on getErrors/getAllErrors.  By default only
  errors encountered without additional validation are added.
  
  See IErrorBag class for documentation.

* Added an AbstractHeader::from which returns IHeader objects from a passed
  header line, or header name/value pair.  Static method can be called on a 
  sub class which returns its type, for example AddressHeader::from would return
  an AddressHeader regardless of the name parameter or name of header in the
  passed line.

* If ParserManagerService can't find a parser for a PartBuilder,
  CompatibleParserNotFoundException will be thrown.  This would only happen if
  customizing the used parsers passed on to ParserManagerService since the
  default set includes a 'catch-all' with NonMimeParserService.

* protected AbstractHeader::setParseHeaderValue renamed to parseHeaderValue, and
  signature changed.

* Can look up comment parts in headers -- use IHeader::getAllParts to return all
  parsed parts including comment parts, or the new getComments() method that
  returns a string array of comments.  ReceivedHeader no longer has protected
  members $comments and $date ($date is now private -- still has
  AbstractHeader::getComments(), and ReceivedHeader::getDateTime()).

* GenericHeader getValue returns a string value of the combination of all its
  non-comment parts.  This applies to any header that doesn't have a more
  specialized header type (not an address, date, id, parameter, received or
  subject header), see HeaderFactory docs for specifics.

* Switched to PHP-DI, users can provide a array|string|DefinitionSource to
  override definitions
  - Renamed service classes to clarify:
    o AbstractParser -> AbstractParserService
    o HeaderParser -> HeaderParserService
    o IParser -> IParserService
    o MessageParser -> MessageParserService
    o MimeParser -> MimeParserService
    o NonMimeParser -> NonMimeParserService
    o ParserManager -> ParserManagerService
    o All consumer classes, e.g. AbstractConsumer -> AbstractConsumerService

* Refactored Header classes to depend on their respective IConsumerService
  classes they need

* Refactored ConsumerService classes to define which sub-ConsumerService classes
  they depend on.  Removed generic 'ConsumerService' class.

* Refactored HeaderPart classes with the following goals:
  - Token classes to be used by Consumers to convert from a string to a "part".
  - When processing a part, consumer may combine them into a 'ContainerPart'
    array to return to a header.
  - Non-Token classes are "ContainerParts" and contain other HeaderParts.
  - When constructing a ContainerPart, other HeaderParts can become children of
    it.
  - HeaderPart is an ErrorBag, so it and all its children can report errors up
    the chain all the way to Message.
