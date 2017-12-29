<?php
namespace protocols\base\commands;
use core\Channel;
use protocols\Command;

/**
 * Class Message
 */
class Message implements Command
{

    public function process(\swoole_websocket_server $_server, int $fd, string $data) {
        $data = explode(' ', $data);

        if (count($data) !== 2) {
            $_server->push($fd, '格式错误：message toId content');
        }
        else {
            $message = new \core\Message(\Broker::getId($fd), $data[0], $data[1]);
            Channel::publish(serialize($message), Channel::CHAT_CHANNEL);
        }
    }
}
