<?php
namespace ZBateson\MailMimeParser\Message\Part;

use php_user_filter;

/**
 * Description of PartStreamFilterManagerTestStreamFilter
 *
 * @author Zaahid Bateson
 */
class PartStreamFilterManagerTestStreamFilter extends php_user_filter
{
    protected static $createCallback = null;
    protected static $closeCallback = null;
    
    public static function setOnCreateCallback($callback)
    {
        self::$createCallback = $callback;
    }
    
    public static function setOnCloseCallback($callback)
    {
        self::$closeCallback = $callback;
    }
    
    public function onClose()
    {
        if (self::$closeCallback !== null) {
            $fn = self::$closeCallback;
            $fn($this->filtername, $this->params);
        }
    }
    
    public function onCreate()
    {
        if (self::$createCallback !== null) {
            $fn = self::$createCallback;
            $fn($this->filtername, $this->params);
        }
        return true;
    }
    
    public function filter($in, $out, &$consumed, $closing)
    {
        return PSFS_PASS_ON;
    }
}
