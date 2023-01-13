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
    // @phpstan-ignore-next-line
    public $firstArg;

    // @phpstan-ignore-next-line
    public $secondArg;

    public function __construct(SplFixedArray $firstArg, $secondArg)
    {
        $this->firstArg = $firstArg;
        $this->secondArg = $secondArg;
    }
}
