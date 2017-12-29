<?php
namespace protocols\base\commands;
use core\Session;
use core\User;
use protocols\Command;

/**
 * Class Login
 */
class Login implements Command
{

    public function process(\swoole_websocket_server $_server, int $fd, string $data) {
        $id = $data;
        \Broker::saveLinkInfo($fd, $id);

        if ( ! ($user = User::create($id)) ) {
            $_server->push($fd, '非法的用户id');
            $_server->close($fd);
        }
        else {
            Session::getInstance()->attach($user);
            $_server->push($fd, 'login ok');
        }
    }
}
