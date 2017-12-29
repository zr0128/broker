<?php
namespace core;
class Log
{

    public static function exception($message) {
        self::write(getenv('SERVER_LOG_FILE'), $message);
    }

    public static function syncException($message) {
        self::write(getenv('SESSION_SYNC_LOG'), $message);
    }

    public static function redisException($message) {
        self::write(getenv('REDIS_EXCEPTION_LOG'), $message);
    }

    public static function chatException($message) {
        self::write(getenv('CHAT_LOG'), $message);
    }

    public static function write($logFile, $message) {
        $fp = fopen($logFile, 'a');
        if ($fp) {
            fputs($fp, $message . "\n", strlen($message) + 1);
            fclose($fp);
        }
    }
}