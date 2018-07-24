<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Helper;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\Part\Factory\MimePartFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\PartBuilderFactory;
use ZBateson\MailMimeParser\Message\Part\Factory\UUEncodedPartFactory;
use ZBateson\MailMimeParser\Message\PartFilter;

/**
 * Provides routines to set or retrieve the signature part of a signed message.
 *
 * @author Zaahid Bateson
 */
final class PrivacyHelper extends AbstractHelper
{
    /**
     * @var GenericHelper a GenericHelper instance
     */
    private $genericHelper;

    /**
     * @var MultipartHelper a MultipartHelper instance
     */
    private $multipartHelper;

    /**
     * @param MimePartFactory $mimePartFactory
     * @param UUEncodedPartFactory $uuEncodedPartFactory
     * @param PartBuilderFactory $partBuilderFactory
     * @param GenericHelper $genericHelper
     * @param MultipartHelper $multipartHelper
     */
    public function __construct(
        MimePartFactory $mimePartFactory,
        UUEncodedPartFactory $uuEncodedPartFactory,
        PartBuilderFactory $partBuilderFactory,
        GenericHelper $genericHelper,
        MultipartHelper $multipartHelper
    ) {
        parent::__construct($mimePartFactory, $uuEncodedPartFactory, $partBuilderFactory);
        $this->genericHelper = $genericHelper;
        $this->multipartHelper = $multipartHelper;
    }

    /**
     * The passed message is set as multipart/signed, and a new part is created
     * below it with content headers, content and children copied from the
     * message.
     *
     * @param Message $message
     * @param string $micalg
     * @param string $protocol
     */
    public function setMessageAsMultipartSigned(Message $message, $micalg, $protocol)
    {
        $this->multipartHelper->enforceMime($message);
        $messagePart = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory)->createMessagePart();
        $this->genericHelper->movePartContentAndChildren($message, $messagePart);
        $message->addChild($messagePart);

        $boundary = $this->multipartHelper->getUniqueBoundary('multipart/signed');
        $message->setRawHeader(
            'Content-Type',
            "multipart/signed;\r\n\tboundary=\"$boundary\";\r\n\tmicalg=\"$micalg\"; protocol=\"$protocol\""
        );
    }

    /**
     * Sets the signature of the message to $body, creating a signature part if
     * one doesn't exist.
     *
     * @param Message $message
     * @param string $body
     */
    public function setSignature(Message $message, $body)
    {
        $signedPart = $message->getSignaturePart();
        if ($signedPart === null) {
            $signedPart = $this->partBuilderFactory->newPartBuilder($this->mimePartFactory)->createMessagePart();
            $message->addChild($signedPart);
        }
        $signedPart->setRawHeader(
            'Content-Type',
            $message->getHeaderParameter('Content-Type', 'protocol')
        );
        $signedPart->setContent($body);
    }

    /**
     * Loops over parts of the message and sets the content-transfer-encoding
     * header to quoted-printable for text/* mime parts, and to base64
     * otherwise for parts that are '8bit' encoded.
     *
     * Used for multipart/signed messages which doesn't support 8bit transfer
     * encodings.
     *
     * @param Message $message
     */
    public function overwrite8bitContentEncoding(Message $message)
    {
        $parts = $message->getAllParts(new PartFilter([
            'headers' => [ PartFilter::FILTER_INCLUDE => [
                'Content-Transfer-Encoding' => '8bit'
            ] ]
        ]));
        foreach ($parts as $part) {
            $contentType = strtolower($part->getHeaderValue('Content-Type', 'text/plain'));
            if ($contentType === 'text/plain' || $contentType === 'text/html') {
                $part->setRawHeader('Content-Transfer-Encoding', 'quoted-printable');
            } else {
                $part->setRawHeader('Content-Transfer-Encoding', 'base64');
            }
        }
    }

    /**
     * Ensures a non-text part comes first in a signed multipart/alternative
     * message as some clients seem to prefer the first content part if the
     * client doesn't understand multipart/signed.
     *
     * @param Message $message
     */
    public function ensureHtmlPartFirstForSignedMessage(Message $message)
    {
        $alt = $message->getPartByMimeType('multipart/alternative');
        if ($alt !== null && $alt instanceof ParentPart) {
            $cont = $this->multipartHelper->getContentPartContainerFromAlternative('text/html', $alt);
            $children = $alt->getChildParts();
            $pos = array_search($cont, $children, true);
            if ($pos !== false && $pos !== 0) {
                $alt->removePart($children[0]);
                $alt->addChild($children[0]);
            }
        }
    }
}
