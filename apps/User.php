<?php
namespace apps;

class User extends \core\User
{

    public $nickname = '';

    /**
     * 验证用户id标识是否合法
     * @param $id mixed 用户唯一标识
     * @return bool
     */
    public function validateId($id): bool {
        return true;
    }

    /**
     * 初始化用户信息
     * @param $id mixed 用户唯一标识
     */
    public function init($id): void {
        $nicks = ['john', '花猫', '憨貔貅', 'jesus', 'god', '突突小怪兽'];
        $this->nickname = isset($nicks[$id]) ? $nicks[$id] : ('guest' . time());
    }
}