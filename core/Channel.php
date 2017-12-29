<?php
namespace core;

class Channel
{

    /**
     * @var \Redis
     */
    private static $_redis = null;

    const SYNC_CHANNEL = 'broker_channel_sync'; // 同步频道
    const CHAT_CHANNEL = 'broker_channel_chat'; // 聊天频道

    public static function subscribe(\Closure $callback, $channel) { // 用户信息同步频道
        try {
            $redis = self::_newRedis();
            $redis->subscribe([$channel], function ($instance, $channelName, $message) use ($callback) {
                $callback($message);
            });
        }
        catch (\RedisException $e) {
            Log::redisException($channel . ':' . $e->getMessage());
        }
    }

    public static function publish(string $message, $channel) {
        try {
            $redis = self::redis();
            if ( ! $redis->publish($channel, $message)) {
                Log::redisException($redis->getLastError());
            }
        }
        catch (\RedisException $e) {
            Log::redisException($channel . ':' . $e->getMessage());
        }
    }

    public static function redis() {
        if (is_null(self::$_redis)) {
            self::$_redis = self::_newRedis();
        }

        return self::$_redis;
    }

    private static function _newRedis() {
        $redis = new \Redis();

        if ( ! $redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT'), getenv('REDIS_TIMEOUT'))) {
            Log::redisException('REDIS服务器连接失败' . $redis->getLastError());
        }

        return $redis;
    }
}