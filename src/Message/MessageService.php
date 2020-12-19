<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message;

use ZBateson\MailMimeParser\Message\Helper\GenericHelper;
use ZBateson\MailMimeParser\Message\Helper\MultipartHelper;
use ZBateson\MailMimeParser\Message\Helper\PrivacyHelper;

/**
 * Responsible for creating helper singletons and Message parts.
 *
 * @author Zaahid Bateson
 */
class MessageService
{
    /**
     * @var GenericHelper the GenericHelper singleton
     */
    private $genericHelper;

    /**
     * @var MultipartHelper the MultipartHelper singleton
     */
    private $multipartHelper;

    /**
     * @var PrivacyHelper the PrivacyHelper singleton
     */
    private $privacyHelper;

    public function __construct(
        GenericHelper $genericHelper,
        MultipartHelper $multipartHelper,
        PrivacyHelper $privacyHelper
    ) {
        $this->genericHelper = $genericHelper;
        $this->multipartHelper = $multipartHelper;
        $this->privacyHelper = $privacyHelper;
    }

    /**
     * Returns the GenericHelper singleton
     * 
     * @return GenericHelper
     */
    public function getGenericHelper()
    {
        return $this->genericHelper;
    }

    /**
     * Returns the MultipartHelper singleton
     *
     * @return MultipartHelper
     */
    public function getMultipartHelper()
    {
        return $this->multipartHelper;
    }

    /**
     * Returns the PrivacyHelper singleton
     *
     * @return PrivacyHelper
     */
    public function getPrivacyHelper()
    {
        return $this->privacyHelper;
    }
}
