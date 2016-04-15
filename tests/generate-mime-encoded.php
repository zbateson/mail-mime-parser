#!/usr/bin/env php
<?php
$value   = $argv[1];
$charset = isset($argv[2]) ? $argv[2] : 'UTF-8';
$scheme  = isset($argv[3]) ? $argv[3] : 'B';
if (!mb_check_encoding($argv[1], 'UTF-8')) {
    $value = mb_convert_encoding($value, 'UTF-8');
}
$encoded = mb_substr(
    iconv_mime_encode(
        '',
        $value,
        ['input-charset' => 'UTF-8', 'output-charset' => $charset, 'scheme' => $scheme]
    ),
    2
);
echo "Encoded: $encoded\n";
$decoded = iconv_mime_decode($encoded, 0, 'UTF-8');
echo "$decoded ", ($decoded == $argv[1]) ? 'equal' : 'not equal', "\n\n";
exit;