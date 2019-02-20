<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

require_once dirname(__DIR__).'/libs/Entrance.php';

require_once dirname(__DIR__).'/libs/HttpEntrance.php';

return [
    'process' => [
        'server_type' => 'process',
        'daemonize' => false,
        'process_name' => 'swover',
        'worker_num' => 2,
        'task_worker_num' => 1,
        'max_request' => 0,
        'log_file' => '/tmp/swoole.log',
        'entrance' => '\\Entrance::process',
    ],

    'http' => [
        'server_type' => 'http',
        'daemonize' => false,
        'process_name' => 'swover',
        'host' => '127.0.0.1',
        'port' => '9501',
        'worker_num' => 2,
        'task_worker_num' => 2,
        'max_request' => 0,
        'log_file' => '/tmp/swoole_http.log',
        'entrance'    => '\\HttpEntrance::http',
        'async'    => false,
        'trace_log'   => true
    ],

    'tcp' => [
        'server_type' => 'tcp',
        'daemonize' => false,
        'process_name' => 'swover',
        'host' => '127.0.0.1',
        'port' => '9502',
        'worker_num' => 2,
        'task_worker_num' => 2,
        'max_request' => 0,
        'log_file' => '/tmp/swoole_tcp.log',
        'entrance'    => '\\Entrance::tcp',
        'async'    => false,
        'trace_log'   => true
    ],
];