#!/usr/bin/php

<?php
set_time_limit(0);
include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/Server.php';

use Dotenv\Dotenv;

/**
 * 在线用户服务
 * @package app\commands
 */
class Broker
{

    public static $config = [];
    /**
     * @var \swoole_table
     */
    public static $users = null;
    /**
     * @var swoole_table
     */
    private static $_idTable = null; // 存储用户id和fd对应关系的内存表
    /**
     * @var swoole_table
     */
    private static $_fdTable = null; // 存储连接fd相关信息的内存表
    /**
     * @var \protocols\WebsocketProtocol
     */
    private static $_protocol = null;

    public static function run() { // ws服务器启动
        self::_initEnv();
        self::_initMemTable();

        new Server();
    }

    public static function saveLinkInfo(int $fd, $id, string $protocol = '') { // 保存用户连接信息
        $row = ['fd' => $fd, 'id' => $id, 'protocol' => $protocol];
        if ( ! $protocol) {
            unset($row['protocol']);
        }

        self::$_fdTable->set($fd, $row);
        self::$_idTable->set($id, ['fd' => $fd]);
    }

    public static function getId(int $fd) { // 获取当前连接用户的id
        return self::$_fdTable->get($fd)['id'] ?? 0;
    }

    public static function getProtocol(int $fd) { // 获取当前连接使用的解析协议
        $protocol = self::$_fdTable->get($fd)['protocol'] ?? '';

        if ( ! $protocol) {
            return null;
        }

        if (is_null(self::$_protocol) || get_class(self::$_protocol) != $protocol) {
            self::$_protocol = new $protocol();
        }

        return self::$_protocol;
    }

    public static function getFd($id) { // 根据用户id获取fd
        return self::$_idTable->get($id)['fd'] ?? '';
    }

    public static function idExists($id) { // 根据用户id判断当前服务区是否有该用户
        return self::$_idTable->exist($id);
    }

    private static function _initEnv() { // 初始化环境变量
        $dotEnv = new Dotenv(__DIR__ . '/config');
        $dotEnv->load();
        $dotEnv->required(['REDIS_HOST', 'REDIS_PORT', 'REDIS_TIMEOUT']);

        self::$config = include __DIR__ . '/config/config.php';
    }

    private static function _initMemTable() { // 初始化内存表和redis
        self::$_idTable = new \swoole_table(getenv('BACKLOG') * 2);
        self::$_idTable->column('fd', swoole_table::TYPE_INT);

        self::$_fdTable = new \swoole_table(getenv('BACKLOG') * 2);
        self::$_fdTable->column('fd', \swoole_table::TYPE_INT);
        self::$_fdTable->column('id', \swoole_table::TYPE_STRING, getenv('MAX_ID_LENGTH'));
        self::$_fdTable->column('protocol', \swoole_table::TYPE_STRING, getenv('MAX_PROTOCOL_CLASS_LEN'));

        // 用户信息内存表
        self::$users = new \swoole_table(getenv('MAX_ONLINE'));
        self::$users->column('user', \swoole_table::TYPE_STRING, getenv('MAX_USER_SERIALIZE'));

        if ( ! self::$_fdTable->create() || ! self::$_idTable->create() || ! self::$users->create()) {
            die('创建内存表失败');
        }
    }
}

Broker::run();