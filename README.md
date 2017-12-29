# Broker

基于websocket的推送服务


### 环境要求
php7.0+
swoole 1.7+
redis 支持pub/sub就行

### 目录描述

- core 核心类文件
- vendor 第三方库文件
- composer.json composer配置文件，并没有放到composer库上
- protocols websocket自定义协议文件
- config 配置文件存放目录
- logs 日志文件存放目录
- Broker.php 可执行的服务器入口文件
- Server.php 基于swoole的websocket类
- apps 实际应用相关类

### 应用场景

1. 访客系统
2. 在线推送系统
3. 实时商品推荐

    
### 运行示例

    先更名config/.env.example为config/.env，
    修改config/.env文件
    按文件注释，把相关配置项改为自己环境相对应的配置。
    把demo里的index.php和jquery.min.js放到一个可访问的web站点下
    浏览器打开两个以上index.php
    打开页面中，点击对方昵称，就可以实时聊天了。
    

### 系统介绍

    采用php7 + swoole做后端服务
    
    系统采用分层设计
    基础服务层，提供基于websocket的通讯机制。
    协议层，可以针对同一websocket连接，采用不同的通讯协议。
    数据同步层，对不同服务器上的数据，实时同步。
    
    扩展性强，支持分布式部署
    各服务之间通过redis进行数据同步和消息转发，他们之间是独立的。


### 嵌入自己的系统

