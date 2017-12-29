<?php
namespace core\processes;

interface IProcess
{

    public static function process(\swoole_server $server);
}