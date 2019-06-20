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
| setting         |   array  | all      | 否 [无]     | [配置选项](https://wiki.swoole.com/wiki/page/274.html) 同一配置出现在setting中会覆盖单独的定义 |

## 开始使用

服务启动后，通过`Request`接收客户端请求，传递`Request`对象给入口函数(通过`entrance`配置)。业务层处理完逻辑后，建议返回`Response`对象、字符串或布尔值给服务。

由于只有Socket、Http才可以从客户端接收数据，而Process只是在当前进程内通过`while(true)`调用入口函数，所以传递给入口函数的是一个空的`Request`对象，当`Response`响应为false或status大于400时，认定为此次业务代码报错，退出死循环，重新拉起新的子进程。

### 启停服务
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
    'async'    => false
];

$class = new \Swover\Server($config);
$class->start(); //启动服务
//$class->stop(); //安全的停止服务
//$class->force(); //强制停止
//$class->restart(); //安全的重启服务
//$class->reload(); //安全的重新加载服务
```

### Request
`\Swoole\Http\Server`、`\Swoole\Server`接收到客户端请求后，将接收的数据传递给`\Swover\Utils\Request`类。

`Request`的构造函数接收`\Swoole\Http\Request` 或 `array`作为参数，实例化后的对象为一个`\ArrayObject`子类，可通过`$request['get']`、`$request->get`或`$request->get()`获取请求参数。

```php
$request = [
    'get' => [],
    'post' => [],
    'input' => '',
    'header' => [],
    'server' => []
];
```

### Response
应用处理完成业务端逻辑后，需要通过服务将响应数据返回给客户端。

返回数据可以是`\Swover\Utils\Response`对象、字符串或布尔值：
- 当返回`Response`对象时，服务会直接调用`send()`或`end()`响应数据到客户端；
- 当返回字符串时，实例化`Response`对象，并将字符串设置为响应消息体；
- 当返回布尔值时，实例化`Response`对象，如果为`false`，设置`status`为500，否则为200


可以在 [samples](./samples) 查看示例。
