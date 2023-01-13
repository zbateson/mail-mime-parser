<?php

namespace ZBateson\MailMimeParser;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Some 'unzip' utilities don't like non-ASCII characters appearing in file
 * names.  Created this test to prevent it from happening as it's happened more
 * than once.
 *
 * @group Base
 * @author Zaahid Bateson
 */
class OnlyAsciiFileNamesTest extends TestCase
{
    public function testFileNames() : void
    {
        $dir = new RecursiveDirectoryIterator(\dirname(__DIR__), FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS);
        $iter = new RecursiveIteratorIterator($dir);
        foreach ($iter as $f) {
            $this->assertTrue(
                \mb_check_encoding($f->getFileName(), 'ASCII'),
                $f->getFileName() . ' contains non-ascii characters, which may '
                    . 'cause problems with some \'unzip\' utilities when '
                    . 'installing via composer'
            );
        }
    }
}
