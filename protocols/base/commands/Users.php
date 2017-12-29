<?php
namespace protocols\base\commands;
use core\Session;
use protocols\Command;

/**
 * Class Users
 */
class Users implements Command
{

    public function process(\swoole_websocket_server $_server, int $fd, string $data) {
        // todo 检测是否有获取用户列表的权限，有的话可以获取哪些用户
        $data = Session::getInstance()->all();
        // todo 根据$dataFormat返回指定格式的数据
        $_server->push($fd, 'users ' . json_encode($data));
    }
}
