<?php
namespace core;

/**
 * Class Session
 * @package core
 */
class Session
{

    private static $_instance = null;

    public $behavior = '';

    protected function __construct() {
    }

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function all() { // 获取所有用户
        $all = [];

        if (count(\Broker::$users)) {
            foreach (\Broker::$users as $user) {
                $all[] = @unserialize($user['user']);
            }
        }

        return $all;
    }

    public function getPage(int $page = 1, \Closure $filter = null) { // 分页获取用户列表
        $items = 10; // todo 放到配置
        $offset = ($page - 1) * $items;
        $users = $this->all();

        if ( ! is_null($filter)) {
            $users = array_filter($users, $filter);
            return $page ? array_slice($users, $offset, $items) : $users;
        }

        return $page ? array_slice($users, $offset, $items) : $users;
    }

    public function attach(User $user) { // 用户上线
        $this->_remove($user);
        $serializeUser = serialize($user);
        $this->saveUser($user->id, $serializeUser);
        $user->update('online', $serializeUser);
    }

    public function detach(User $user) { // 用户下线
        $this->_remove($user);
        $user->update('offline', serialize($user));
    }

    public function saveUser($id, $serializeUser) {
        \Broker::$users->set($id, ['user' => $serializeUser]);
    }

    private function _remove(User $user) {
        if (\Broker::$users->exist($user->id)) {
            \Broker::$users->del($user->id);
        }
    }
}