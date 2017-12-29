<?php
return [
    'redis' => [ // redis 配置
        'host' => getenv('REDIS_HOST'),
        'port' => getenv('REDIS_PORT'), // 端口
        'timeout' => getenv('REDIS_TIMEOUT'), // 超时
    ],
    'processes' => [
        'core\processes\SessionSync',
        'core\processes\Chat',
    ],
    'user' => [
        'class' => 'apps\User',
    ],
    'auth' => [
        'class' => 'apps\Auth',
    ]
];
