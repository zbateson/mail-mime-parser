<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser\Message\Part;

/**
 * Responsible for creating PartStreamFilterManager instances.
 *
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerFactory
{
    protected $quotedPrintableDecodeFilter;
    protected $base64DecodeFilter;
    protected $uudecodeFilter;
    protected $charsetConversionFilter;
    
    /**
     * 
     * @param string $quotedPrintableDecodeFilter
     * @param string $base64DecodeFilter
     * @param string $uudecodeFilter
     * @param string $charsetConversionFilter
     */
    public function __construct(
        $quotedPrintableDecodeFilter,
        $base64DecodeFilter,
        $uudecodeFilter,
        $charsetConversionFilter
    ) {
        $this->quotedPrintableDecodeFilter = $quotedPrintableDecodeFilter;
        $this->base64DecodeFilter = $base64DecodeFilter;
        $this->uudecodeFilter = $uudecodeFilter;
        $this->charsetConversionFilter = $charsetConversionFilter;
    }
    
    /**
     * Constructs a new PartStreamFilterManager object and returns it.
     * 
     * @return \ZBateson\MailMimeParser\Message\Part\PartStreamFilterManager
     */
    public function newInstance()
    {
        return new PartStreamFilterManager(
            $this->quotedPrintableDecodeFilter,
            $this->base64DecodeFilter,
            $this->uudecodeFilter,
            $this->charsetConversionFilter
        );
    }
}
