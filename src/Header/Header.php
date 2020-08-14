<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Header;

abstract class Header
{
    const FROM = 'from';
    const TO = 'to';
    const SUBJECT = 'subject';
    const MESSAGE_ID = 'message-id';
    const CONTENT_TYPE = 'content-type';
    const CC = 'cc';
    const BCC = 'bcc';
    const DATE = 'date';
    const SENDER = 'sender';
    const REPLY_TO = 'reply-to';
    const IN_REPLY_TO = 'in-reply-to';
    const REFERENCES = 'references';
    const AUTO_SUBMITTED = 'auto-submitted'; // https://tools.ietf.org/html/rfc3834#section-5
    const MIME_VERSION = 'mime-version'; // https://tools.ietf.org/html/rfc2045#section-4
}
