<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Factory;

use ZBateson\MailMimeParser\Header\HeaderFactory;
use ZBateson\MailMimeParser\Message\PartHeaderContainer;

/**
 * Description of HeaderContainerFactory
 *
 * @author Zaahid Bateson
 */
class HeaderContainerFactory
{
    /**
     * @var HeaderFactory the HeaderFactory passed to HeaderContainer instances.
     */
    protected $headerFactory;

    /**
     * Constructor
     *
     * @param HeaderFactory $headerFactory
     */
    public function __construct(HeaderFactory $headerFactory)
    {
        $this->headerFactory = $headerFactory;
    }

    /**
     * Creates and returns a HeaderContainer.
     *
     * @return HeaderContainer;
     */
    public function newInstance(PartHeaderContainer $from = null)
    {
        return new PartHeaderContainer($this->headerFactory, $from);
    }
}
