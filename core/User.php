<?php
namespace core;

/**
 * Class User
 */
abstract class User
{

    const USERS_HASH_KEY = 'users_session_hash'; // 用户集合的redis key

    public $id; // 唯一标识
    public $behaviors = [];

    public static function create($id) { // 实例化用户对象
        /* @var $user \core\User */
        $user = new \Broker::$config['user']['class']($id);
        $user->id = $id;
        $user->init($id);

        return $user->validateId($id) ? $user : false;
    }

    public function update(string $behavior, string $data) {
        $this->_updateBehavior($behavior, $data);
        $serializeData = serialize($this);

        try {
            ($behavior == 'offline')
                ? Channel::redis()->hDel(self::USERS_HASH_KEY, $this->id)
                : Channel::redis()->hSet(self::USERS_HASH_KEY, $this->id, $serializeData);
        }
        catch (\RedisException $e) {
            Log::redisException($e->getMessage());
        }

        Channel::publish(json_encode([$behavior, $serializeData]), Channel::SYNC_CHANNEL);
    }

    private function _updateBehavior(string $behavior, $data) { // 更新用户行为
        $maxSize = getenv('MAX_BEHAVIOR_HISTORY');
        if (count($this->behaviors) == $maxSize) {
            unset($this->behaviors[$maxSize - 1]);
        }

        array_unshift($this->behaviors, [$behavior, $data]);
    }

    public function __sleep() {
        return ['id', 'behaviors'];
    }

    public function __wakeup() {
        $this->init($this->id);
    }

    /**
     * 验证用户id标识是否合法
     * @param $id mixed 用户唯一标识
     * @return bool
     */
    abstract public function validateId($id): bool;

    /**
     * 初始化用户信息
     * @param $id mixed 用户唯一标识
     */
    abstract public function init($id): void;
}