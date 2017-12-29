<?php
class Server
{

    public function __construct() {
        $server = new swoole_websocket_server("0.0.0.0", getenv('SERVER_PORT'), SWOOLE_PROCESS);

        $server->set($this->_settings());
        $server->on('Start', [$this, 'start']);
        $server->on('WorkerStart', [$this, 'workerStart']);
        $server->on('open', [$this, 'open']);
        $server->on('message', [$this, 'message']);
        $server->on('close', [$this, 'close']);

        if (isset(Broker::$config['processes']) && ! empty(Broker::$config['processes'])) {
            foreach (Broker::$config['processes'] as $process) {
                /* @var $process \core\processes\IProcess */
                $server->addProcess($process::process($server));
            }
        }
        $server->start();
    }

    public function start(swoole_server $server) {
        file_put_contents(getenv('PID_FILE'), $server->master_pid);

        $redis = \core\Channel::redis();
        if ($users = $redis->hGetAll(\core\User::USERS_HASH_KEY)) {
            foreach ($users as $k => $serializeUser) { // 初始化用户列表（服务重启的情况下）
                $user = unserialize($serializeUser);
                if (is_a($user, \core\User::class)) {
                    if (is_null($user->id)) {
                        $redis->hDel(\core\User::USERS_HASH_KEY, $k);
                        continue;
                    }
                    \core\Session::getInstance()->saveUser($user->id, $serializeUser);
                }
            }
        }
    }

    public function workerStart(swoole_websocket_server $server) {
        swoole_timer_tick(getenv('HEARTBEAT_CHECK_INTERVAL') * 1000, function() use($server) { // 关闭超时连接
            array_map(function($fd) use($server) {
                $server->push($fd, '超时关闭');
                $server->close($fd);
                $user = \core\User::create(Broker::getId($fd));
                $user && \core\Session::getInstance()->detach($user); // 通知用户下线
            }, $server->heartbeat() ?: []);
        });
    }

    public function open(swoole_websocket_server $_server, $frame) {
        if ( ! \core\Auth::validate($frame->get['token'] ?? '')) {
            $_server->close($frame->fd);
        }
        $_server->push($frame->fd, 'fd ' . $frame->fd);
    }

    public function message(swoole_websocket_server $_server, $frame) {
        $frameDatas = explode(' ', $frame->data);

        switch ($frameDatas[0]) {
            case '^^': // 心跳检测
            break;
            case 'protocol': // 协商协议
                $protocol = $frameDatas[1] ?? '';
                if ( ! $protocol || ! in_array($protocol, explode(',', getenv('WS_PROTOCOLS'))) ) {
                    $_server->push($frame->fd, '协议不存在');
                }
                else {
                    Broker::saveLinkInfo($frame->fd, '', 'protocols\\' . ucfirst($protocol));

                    $_server->push($frame->fd, 'protocol ready');
                }
            break;
            default:
                /** @var $protocol \protocols\WebsocketProtocol */
                $protocol = Broker::getProtocol($frame->fd);

                is_null($protocol)
                    ? $_server->push($frame->fd, '未指定协议')
                    : $protocol->message($_server, $frame);
        }
    }

    public function close(swoole_websocket_server $_server, int $fd) {
        $id = Broker::getId($fd);
        $user = $id ? \core\User::create(Broker::getId($fd)) : false;
        $user && \core\Session::getInstance()->detach($user); // 通知用户下线
    }

    private static function _settings() {
        $settings = [
            'max_conn' => getenv('MAX_CONNECTION'), // 最大允许的连接个数
            'dispatch_mode' => 3, // worker于Reactor通信方式采用忙闲分配策略
            //'daemonize' => 1, // 开启守护进程模式
            'reactor_num' => getenv('REACTOR_NUM'), // reactor 线程数量
            'worker_num' => getenv('WORKER_NUM'),    // worker 进程数量
            'backlog' => getenv('BACKLOG'),   // listen backlog
            'max_request' => getenv('MAX_REQUEST'), // 每个连接处理30个请求重启
            // 'heartbeat_check_interval' => getenv('HEARTBEAT_CHECK_INTERVAL'), // 心跳检测时间，手动调用$server->heartbeat()就不要设置这个
            'heartbeat_idle_time' => getenv('HEARTBEAT_IDLE_TIME'), // 连接最大空闲时间
            'open_tcp_nodelay' => false,
            'log_file' => getenv('SERVER_LOG_FILE'),
        ];

        if ($user = getenv('USER')) {
            $settings['user'] = $user;
        }

        if ($group = getenv('GROUP')) {
            $settings['group'] = $group;
        }

        return $settings;
    }
}