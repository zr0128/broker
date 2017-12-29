<?php
namespace apps;

class Auth extends \core\Auth
{


    /**
     * 验证建立连接的用户是否是合法用户
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool {
        return true;
    }
}