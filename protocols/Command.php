<?php
namespace protocols;

interface Command
{
    public function process(\swoole_websocket_server $_server, int $fd, string $data);
}