#!/usr/bin/env php
<?php
$value   = $argv[1];
$charset = isset($argv[2]) ? $argv[2] : 'UTF-8';

$encoded = iconv('UTF-8', $charset . '//TRANSLIT//IGNORE', $value);
$encoded2 = mb_convert_encoding($value, $charset, 'UTF-8');

echo "Encoded: $encoded\n";
echo "Encoded2: $encoded2\n";
exit;
