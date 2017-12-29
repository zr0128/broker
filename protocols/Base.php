<?php
namespace protocols;

use core\Log;

include_once 'WebsocketProtocol.php';

/**
 * Class Chat
 */
class Base implements WebsocketProtocol
{

    private static $_commands = [];

    public function message(\swoole_websocket_server $_server, $frame) { // 处理消息
        $data = $frame->data;
        if ( ! strpos($data, ' ')) {
            return $_server->push($frame->fd, '错误的命令格式');
        }

        list($command, $data) = explode(' ', $data, 2);
        if ($command != 'login' && ! \Broker::getId($frame->fd)) {
            return $_server->push($frame->fd, '请先登录');
        }

        try {
            $commandReflected = new \ReflectionClass('\\protocols\\base\\commands\\' . ucfirst($command));
            if ($commandReflected->implementsInterface(Command::class)) {
                self::$_commands[$command] = $commandReflected->newInstance();
            }
            else {
                Log::exception('命令解析对象没有实现protocols\Command接口');

                return $_server->push($frame->fd, '指令解析错误');
            }
        }
        catch (\ReflectionException $e) {
            return $_server->push($frame->fd, '不支持的命令');
        }

        self::$_commands[$command]->process($_server, $frame->fd, $data);
    }
}
