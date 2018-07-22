<?php

namespace ZBateson\MailMimeParser;

/**
 * Description of SignedMessage
 *
 * @author Zaahid Bateson
 */
class SignedMessage extends Message
{
    /**
     * Returns a string containing the entire body of a signed message for
     * verification.
     *
     * @return string or null if the message doesn't have any children, or the
     *      child returns null for getHandle
     */
    public function getSignedMessageAsString()
    {
        $child = $this->getChild(0);
        if ($child !== null && $child->getHandle() !== null) {
            $normalized = preg_replace(
                '/\r\n|\r|\n/',
                "\r\n",
                stream_get_contents($child->getHandle())
            );
            return $normalized;
        }
        return null;
    }

    /**
     * Returns the signature part of a multipart/signed message or null.
     *
     * The signature part is determined to always be the 2nd child of a
     * multipart/signed message, the first being the 'body'.
     *
     * Using the 'protocol' parameter of the Content-Type header is unreliable
     * in some instances (for instance a difference of x-pgp-signature versus
     * pgp-signature).
     *
     * @return MimePart
     */
    public function getSignaturePart()
    {
        $contentType = $this->getHeaderValue('Content-Type', 'text/plain');
        if (strcasecmp($contentType, 'multipart/signed') === 0) {
            return $this->getChild(1);
        } else {
            return null;
        }
    }

}
