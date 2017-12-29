<?php
namespace core;

/**
 * 连接认证
 * Class Auth
 */
abstract class Auth
{

    public static function validate(string $token) { // 实例化用户对象
        /* @var $auth \core\Auth */
        $auth = new \Broker::$config['auth']['class']();

        return $auth->validateToken($token);
    }

    /**
     * 验证建立连接的用户是否是合法用户
     * @param string $token
     * @return bool
     */
    abstract public function validateToken(string $token): bool;
}