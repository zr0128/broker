<?php
namespace protocols;

/**
 * websocket传输协议
 * Class Protocol
 */
interface WebsocketProtocol
{

    public function message(\swoole_websocket_server $server, $frame);
}
