<?php
namespace core\processes;

use core\Log;
use core\Channel;
use core\Message;

class Chat implements IProcess
{

    public static function process(\swoole_server $server) { // 接收其他服务器发送来的订阅消息
        return new \swoole_process(function (/*$process*/) use($server) {
            ini_set('default_socket_timeout', -1);

            Channel::subscribe(function($message) use($server) {
                $messageObj = @unserialize($message);

                if ( ! is_object($messageObj) || ! is_a($messageObj, Message::class)) {
                    Log::syncException('[CHAT]无效的订阅消息' . $message);
                }
                else {
                    // 非本服务器用户，则对所以客户端下发用户信息/行为更新消息
                    if (\Broker::idExists($messageObj->toId)) { // 用户在当前服务器上
                        /** @var $server \swoole_websocket_server */
                        if (is_object($messageObj) && is_a($messageObj, Message::class)) {
                            $fd = \Broker::getFd($messageObj->toId);
                            if ($server->exist($fd)) {
                                $server->push($fd, 'message ' . json_encode($messageObj));
                            }
                            else {
                                // todo 消息未能送达
                            }
                        }
                    }
                }
            }, Channel::CHAT_CHANNEL);
        });
    }
}