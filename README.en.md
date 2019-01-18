[中文文档](./README.md)
# Swoole Server

Swover is a Server-Framework based on Swoole extension. Provide support for HTTP,TCP and Process.

## Requirements

Swover has the following requirements:

- PHP version 5.6 or later.
- [Swoole Extension](http://pecl.php.net/package/swoole) 1.9.5 or later.
- [ruesin/utils](https://github.com/ruesin/utils)

## Installation

Add Swover to composer.json configuration file.

`$ composer require ruesin/swover`

And update the composer

`$ composer update`

## Configuration

| config          |  type  | scene    | desc                                                         |
| --------------- | :----: | :------- | ------------------------------------------------------------ |
| server_type     | string | all      | This server's type, can use http,tcp,process                 |
| daemonize       |  bool  | all      | IS server run daemon way?                                    |
| process_name    | string | all      | The server process name                                      |
| worker_num      |  int   | all      | worker process number                                        |
| task_worker_num |  int   | tcp,http | task worker process number                                   |
| host            | string | tcp,http | The server bind host                                         |
| port            |  int   | tcp,http | The server bind port                                         |
| max_request     |  int   | all      | when process execute count bigger than this value, this process will restart. if is zero, never restart. |
| log_file        | string | all      | The default log position. When daemonize is true, child-process 'echo,print_r,var_dump' will save to this file. |
| entrance        | string | all      | The entrance to the application. If has not method, default is 'run'. |
| async           |  bool  | tcp,http | if true, worker will asynchronous execution, or synchronized block execution |
| signature       | string | tcp,http | if define, will call this function to verify signature, or do not. |
| trace_log       |  bool  | tcp,http | if true, when woker process 'connect','receive','task','finish','close', will save trace to log_file |

## Get Started

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

More information please see the [samples](./samples).