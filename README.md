# Swover

Swover是一个基于Swoole扩展的服务类库，提供基础的HTTP、TCP、Process服务能力，不包含任何业务代码，只需简单配置即可安全使用，对业务代码完全无侵入。

## 依赖

- PHP 5.6 及之后版本
- [Swoole Extension](http://pecl.php.net/package/swoole) 1.9.5 及更新版本
- pcntl-extension
- posix-extension

> 虽然提供了对 PHP5 及 swoole 1.x 的支持，但仍建议升级到最新版。

## 安装

添加swover到composer.json文件，然后更新composer。

```
$ composer require swover/swover
$ composer update
```

## 配置项

| 配置             | 类型     | 场景      | 必填 |   默认 | 描述                                                             |
| --------------- | :------: | :------- | :-- | :----  | ------------------------------------------------------------    |
| server_type     |  string  | all      |**Y**|        | 服务类型，可选项：http,tcp,process                                 |
| daemonize       |   bool   | all      |  N  | false  | 服务是否以守护进程方式运行                                           |
| process_name    |  string  | all      |  N  | server | 服务的进程名，单节点内应唯一                                         |
| worker_num      |   int    | all      |  N  | 1      | worker 进程数                                                     |
| task_worker_num |   int    | tcp,http |  N  | 0      | task-worker 进程数                                                |
| host            |  string  | tcp,http |  N  | 0.0.0.0| 监听地址                                                          |
| port            |   int    | tcp,http |  N  | 0      | 监听端口，`0`表示随机获取一个可用端口                                  |
| max_request     |   int    | all      |  N  | 0      | 进程最大执行次数，超过时安全重启。`0`表示永不重启，为避免内存泄漏，建议设置  |
| entrance        |  string  | all      |**Y**|        | 业务逻辑的入口，必须是可被调用的函数或方法，接收`request`返回`response`   |
| async           |   bool   | tcp,http |  N  | false  | 是否异步执行，如果为`true`，接收到请求后，转发给`task`异步处理            |
| setting         |   array  | all      |  N  |        | [配置选项](https://wiki.swoole.com/wiki/page/274.html)             |
| events          |   array  | all      |  N  |        | 事件注册，支持二维数组，数组下标无实际作用。[事件](#Event)                |

## 开始使用

### 服务类型
`Swover`提供了三种服务类型可用：
- Tcp：`Tcp`类型的服务基于`\Swoole\Server`处理网络请求与响应
- Http：`Http`类型的服务基于`\Swoole\Http\Server`处理网络请求与响应
- Process：通过`\Swoole\Process`创建子进程，在进程内执行`while(true)`调用业务入口，需在入口内处理输入输出

通过服务启动时传入的`server_type`配置项决定服务类型。

### 启停服务
```php
$config = [
    'server_type' => 'tcp',
    'daemonize' => false,
    'process_name' => 'swover',
    'worker_num' => 2,  //会被setting内的同名配置覆盖
    'task_worker_num' => 1,
    'max_request' => 0,
    'entrance' => '\\Entrance::process',
    'host' => '127.0.0.1',
    'port' => '9501',
    'async'    => false,
    'setting' => [
        'worker_num' => 3, //覆盖外层同名配置，最终服务启动时的 worker_num 为 3
        'log_file' => '/tmp/swoole_tcp.log',
    ],
    'events' => [ //事件注册可以为字符串、对象、闭包
        'master_start' => [
            '\MasterStartA',
            new \MasterStartB(),
        ],
        'worker_start' => '\WorkerStart',
        function ($request) { },
    ]
];

$class = new \Swover\Server($config);
$class->start(); //启动服务
//$class->stop(); //安全的停止服务
//$class->force(); //强制停止
//$class->restart(); //安全的重启服务
//$class->reload(); //安全的重新加载服务
```

服务启动后，通过`Request`接收客户端请求，将`Request`对象作为参数传递给入口函数。

入口函数处理完业务逻辑后返回结果，建议返回`Response`对象、字符串或布尔值。

### Request
服务接收到客户端数据后，构造`\Swover\Utils\Request`对象，构造函数接收参数为：
- `\Swoole\Http\Request`：HTTP服务类型
- `array`：TCP服务类型

对象继承自`\ArrayObject`，可通过`$request['get']`、`$request->get`或`$request->get()`获取请求参数。

```php
// curl http://127.0.0.1:9501/user/info?id=123

function entrance(\Swover\Utils\Request $request)
{
    var_dump($request->path());
    // string(10) "/user/info"

    var_dump($request->get());
    // array(1) {
    //   ["id"]=>
    //   string(3) "123"
    // }

    var_dump($request->get('name', 'ruesin'));
    // string(6) "ruesin"

    var_dump($request->get);
    // array(1) {
    //   ["id"]=>
    //   string(3) "123"
    // }

    var_dump($request->get['id']);
    // string(3) "123"
}
```

> 由于`Process`服务只是通过`while(true)`调用入口函数，所以传递给入口函数的是一个空的`Request`对象。

### Response
入口函数完成后返回数据，构造`\Swover\Utils\Response`对象，响应结果到客户端，数据类型：

- `\Swover\Utils\Response`对象
- 字符串：构造`Response`对象，设置字符串为响应消息体；
- 布尔值：构造`Response`对象，如果`false`设置`status`为500，否则为200

Response 实现：
```php
// \Swover\Server\Base::class
protected function entrance($request)
{
    $result = call_user_func_array($this->entrance, [$request]);

    if ($result instanceof \Swover\Contracts\Response) {
        $response = $result;
    } else {
        $response = new Response();
    }

    if (is_string($result) || is_numeric($result)) {
        $response->setBody($result);
    }

    if (is_bool($result) && $result === false) {
        $response->setCode(500);
    }

    return $response;
}
```

业务入口：
```php
// 返回字符串，将被设置为 response body
function entranceA(\Swover\Utils\Request $request)
{
    return "This is response body.";
}

// 返回 false，将 http code 设置为500
function entranceB(\Swover\Utils\Request $request)
{
    return false;
}

// 返回 response 对象
function entranceC(\Swover\Utils\Request $request)
{
    $response = new \Swover\Utils\Response();
    $response->setBody('{"status":-1,"msg":"Has Not Route!"}');
    $response->setCode(404);
    $response->setHeader('Content-Type', 'application/json');
    return $response;
}
```

> 在`Process`服务中，当`status`大于400时，判定终止此次循环，将重启worker进程。

### Event
提供了事件机制，可以通过服务启动前传入配置 或 调用`\Swover\Utils\Events`注册事件。

此处提供的事件，主要是为了扩展Swoole的事件回调，事件按照注册顺序先后触发，可使用`Event::before()`方法将方法插入队头。

注册的回调方法：
- 自定义类：必须有表示事件类型的`EVENT_TYPE`静态属性，时间触发的`trigger(...$params)`方法
- 闭包 `function(...$params){}`

除`Request`和`Response`两个事件为特殊定义，其余事件的参数均须与[Swoole事件](https://wiki.swoole.com/wiki/page/41.html)严格一致。

支持的事件类型定义在`\Swover\Contracts\Events`接口中，建议绑定事件时直接使用预定义常量，避免触发失败。

> 事件类型大小写不敏感，最终绑定均转为小写。

TCP的`receive`和HTTP的`request`事件统一为`request`事件，参数及使用定义在`\Swover\Utils\Event\Request`。

`response`事件参数及使用定义在`\Swover\Utils\Event\Response`。

```php

$instance = \Swover\Utils\Event::getInstance();

// 绑定闭包
$instance->bindInstance(\Swover\Contracts\Events::START, 'master_start_alias_a', function (\Swoole\Server $server) {
    echo "Swoole server is started\n";
});

// 绑定对象
$instance->bind(new WorkerStartEvent());

// 绑定类名，自动解析为对象
$instance->bind('WorkerStartEvent');

//自定义类，必须定义 EVENT_TYPE、trigger()
class WorkerStartEvent
{
    const EVENT_TYPE = \Swover\Contracts\Events::WORKER_START;

    public function trigger($server, $worker_id)
    {
        echo $worker_id . PHP_EOL;
    }
}

// 实现了 Request 接口，接口已定义 EVENT_TYPE，只需实现 trigger()
class RequestEvent implements \Swover\Contracts\Events\Request
{
    public function trigger($server, $request)
    {
        echo $request->path . PHP_EOL;
    }
}
```

### Worker
提供获取当前进程状态、主进程状态的方法。

父进程收到`SIGCHLD`信号后，子进程调用`Worker::getStatus()`时，会触发`pcntl_signal_dispatch()`注册的事件，将当前进程状态设置为`false`。

利用此特性，在业务内判断当前进程的状态是否需要退出。
```php
while(true) {
    if (\Swover\Worker::getStatus() == false) {
        echo "Worker process is finish.";
        break;
    }
    //do something
}
```

`Worker::checkProcess($pid)`用来检测指定进程ID是否正常存活，可用于在子进程中检测父进程的状态，当父进程不存在时退出当前进程。
```php
while(true) {
    if (\Swover\Worker::checkProcess(\Swover\Worker::getMasterPid()) == false) {
        echo "Parent process has gone away.";
        break;
    }
    //do something
}
```

## 示例
- [示例](./samples) 
- [测试用例](./tests)
