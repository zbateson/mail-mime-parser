<?php
namespace ZBateson\MailMimeParser;

use SplFixedArray;

/**
 * Sample class to test Container
 *
 * @author Zaahid Bateson
 */
class ContainerTestClass
{
    public $firstArg;
    public $secondArg;

    public function __construct(SplFixedArray $firstArg, $secondArg)
    {
        $this->firstArg = $firstArg;
        $this->secondArg = $secondArg;
    }
}
