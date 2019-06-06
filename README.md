# Swover

Swover是一个基于Swoole扩展的server框架，提供HTTP、TCP、Process能力。只需简单配置即可使用，对业务代码完全无侵入。

## 依赖

- PHP 5.6及之后版本。
- [Swoole Extension](http://pecl.php.net/package/swoole) 1.9.5 及更新版本.
- pcntl-extension

## 安装

添加swover到composer.json文件，然后更新composer。

```
$ composer require swover/swover
$ composer update
```

## 配置项

| 配置            | 数据类型 | 场景     | 必填 & 默认    | 描述                                                         |
| --------------- | :------: | :------- | :------- | ------------------------------------------------------------ |
| server_type     |  string  | all      | 是 [无]      | 服务类型，可选项：http,tcp,process                           |
| daemonize       |   bool   | all      | 是 [false]     | 服务是否以守护进程方式运行                                   |
| process_name    |  string  | all      | 是 [server]     | 服务的进程名，建议单机内唯一                                 |
| worker_num      |   int    | all      | 是 [1]     | worker 进程数                                                |
| task_worker_num |   int    | tcp,http | 是 [1]     | task-worker 进程数                                           |
| host            |  string  | tcp,http | 是 [无]     | 监听地址                                                     |
| port            |   int    | tcp,http | 是 [无]     | 监听端口                                                     |
| max_request     |   int    | all      | 否 [0]     | 进程最大执行次数，超过这个值时，进程会安全重启。如果设置为0，则永远不会重启 |
| entrance        |  string  | all      | 是 [无]     | 业务代码的入口文件，会从server中执行，必须指定类名，方法名默认是run |
| async           |   bool   | tcp,http | 否 [false]     | 是否异步执行，如果为true，接收到请求后，会将请求分发到task-worker，并立即响应 |
| trace_log       |   bool   | tcp,http | 否 [false]     | 如果为true，woker 进程的 'connect','receive','task','finish','close' 事件会输出日志 |
| setting         |   array  | all      | 否 [无]     | [配置选项](https://wiki.swoole.com/wiki/page/274.html) 同一配置出现在setting中会覆盖单独的定义 |

## 开始使用

```php
$config = [
    'server_type' => 'tcp',
    'daemonize' => false,
    'process_name' => 'swover',
    'worker_num' => 2,
    'task_worker_num' => 1,
    'max_request' => 0,
    'log_file' => '/tmp/swoole.log',
    'entrance' => '\\Entrance::process',
    'host' => '127.0.0.1',
    'port' => '9501',
    'async'    => false,
    'trace_log'   => true
];

$class = new \Swover\Server($config);
$class->start(); //启动服务
//$class->stop(); //安全的停止服务
//$class->force(); //强制停止
//$class->restart(); //安全的重启服务
//$class->reload(); //安全的重新加载服务
```

可以在 [samples](./samples) 查看示例。
