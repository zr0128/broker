<?php
namespace core\processes;

use core\Log;
use core\Channel;
use core\Session;
use core\User;

class SessionSync implements IProcess
{

    public static function process(\swoole_server $server) { // 接收其他服务器发送来的订阅消息
        return new \swoole_process(function (/*$process*/) use($server) {
            ini_set('default_socket_timeout', -1);

            while (true) {
                Channel::subscribe(function ($message) use ($server) {
                    /** @var $server \swoole_websocket_server */
                    $decodeMessage = json_decode($message, true);

                    if (json_last_error() != JSON_ERROR_NONE || count($decodeMessage) < 2) {
                        Log::syncException('[SYNC EXCEPTION]无效的订阅消息' . $message);
                    }
                    else {
                        list($behavior, $serializeUser) = $decodeMessage;
                        /** @var $user User*/
                        $user = @unserialize($serializeUser);

                        // 非本服务器用户，则对所以客户端下发用户信息/行为更新消息
                        if (is_a($user, User::class)) {
                            $session = Session::getInstance();
                            $behavior != 'offline' && $session->saveUser($user->id, $serializeUser);

                            foreach ($server->connections as $fd) {
                                $server->push($fd, 'behavior ' . $behavior . ' ' . json_encode($user));
                            }
                        }
                    }
                }, Channel::SYNC_CHANNEL);
            }
        });
    }
}
