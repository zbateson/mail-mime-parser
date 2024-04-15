<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Processor\PsrLogMessageProcessor;

function mmpGetAndClearTestHandler() : TestHandler {
    static $handler = null;
    if ($handler === null) {
        $handler = new TestHandler('debug');
    }
    $handler->clear();
    return $handler;
}

function mmpGetTestLogger() : Logger {
    static $logger = null;
    \mmpGetAndClearTestHandler();
    if ($logger === null) {
        $logger = new Logger('testing');
        $logger->pushProcessor(new PsrLogMessageProcessor(removeUsedContextFields: true));
        @\unlink(__DIR__ . '/' . TEST_LOG_FILE);
        $logger->pushHandler(new StreamHandler(__DIR__ . '/' . TEST_LOG_FILE, 'debug'));
        $logger->pushHandler(mmpGetAndClearTestHandler());
    }
    return $logger;
}
