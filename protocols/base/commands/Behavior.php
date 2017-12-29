<?php
namespace protocols\base\commands;
use core\Session;
use core\User;
use protocols\Command;

/**
 * Class Behavior
 */
class Behavior implements Command
{

    public function process(\swoole_websocket_server $_server, int $fd, string $data) {
        $data = explode(' ', $data);
        $user = User::create(\Broker::getId($fd));

        $user->update($data, $data[1] ?? '');
    }
}
