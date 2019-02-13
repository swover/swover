# Swover

Swover是一个基于Swoole扩展的server框架，提供HTTP、TCP、Process能力。只需简单配置即可使用，对业务代码完全无侵入。

## 依赖

- PHP 5.6及之后版本。
- [Swoole Extension](http://pecl.php.net/package/swoole) 1.9.5 及更新版本.

## 安装

添加swover到composer.json文件，然后更新composer。

```
$ composer require swover/swover
$ composer update
```

## 配置项

| 配置            | 数据类型 | 场景     | 描述                                                         |
| --------------- | :------: | :------- | ------------------------------------------------------------ |
| server_type     |  string  | all      | 服务类型，可选项：http,tcp,process                           |
| daemonize       |   bool   | all      | 服务是否以守护进程方式运行                                   |
| process_name    |  string  | all      | 服务的进程名，建议单机内唯一                                 |
| worker_num      |   int    | all      | worker 进程数                                                |
| task_worker_num |   int    | tcp,http | task-worker 进程数                                           |
| host            |  string  | tcp,http | 监听地址                                                     |
| port            |   int    | tcp,http | 监听端口                                                     |
| max_request     |   int    | all      | 进程最大执行次数，超过这个值时，进程会安全重启。如果设置为0，则永远不会重启 |
| log_file        |  string  | all      | 默认日志文件。如果 daemonize 设置为 true, 子进程的输出 'echo,print_r,var_dump' 会保存到这个文件中 |
| entrance        |  string  | all      | 业务代码的入口文件，会从server中执行，必须指定类名，方法名默认是run |
| async           |   bool   | tcp,http | 是否异步执行，如果为true，接收到请求后，会将请求分发到task-worker，并立即响应success |
| signature       |  string  | tcp,http | 验证签名的方法，为空则不验证签名。                           |
| trace_log       |   bool   | tcp,http | 如果为true，woker 进程的 'connect','receive','task','finish','close' 事件会记录日志到log_file中 |

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
    'signature'   => '\\Sign::verify',
    'trace_log'   => true
];

$class = new \Swover\Server($config);
$class->start();
//$class->stop();
//$class->restart();
//$class->reload();
```

可以在 [samples](./samples) 查看示例。
