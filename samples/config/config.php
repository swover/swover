<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

call_user_func(function () {
    $dirs = [
        dirname(__DIR__) . '/libs/',
        dirname(__DIR__) . '/events/',
    ];

    foreach ($dirs as $dir) {
        $handler = opendir($dir);
        while ((($filename = readdir($handler)) !== false)) {
            if (substr($filename, -4) == '.php') {
                require_once $dir . '/' . $filename;
            }
        }
        closedir($handler);
    }
});

function configs()
{
    return [
        'process' => [
            'server_type' => 'process',
            'daemonize' => false,
            'process_name' => 'swover',
            'worker_num' => 2,
            'task_worker_num' => 1,
            'max_request' => 0,
            'setting' => [
                'log_file' => '/tmp/swoole.log',
            ],
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
            'entrance' => '\\Entrance::http',
            'async' => false,
            'setting' => [
                'log_file' => '/tmp/swoole.log',
                'worker_num' => 3,
            ],
            'events' => [
                'master_start' => '\MasterStart',
                'worker_start' => '\WorkerStart',
            ]
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
            'setting' => [
                'log_file' => '/tmp/swoole_tcp.log',
            ],
            'entrance' => '\\Entrance::tcp',
            'async' => false
        ],
        'websocket' => [
            'server_type' => 'websocket',
            'daemonize' => false,
            'process_name' => 'swover',
            'host' => '127.0.0.1',
            'port' => '9501',
            'worker_num' => 1,
            'task_worker_num' => 1,
            'max_request' => 0,
            'entrance' => '\\Entrance::http',
            'async' => false,
            'setting' => [
                'log_file' => '/tmp/swoole.log',
                'worker_num' => 3,
            ],
            'events' => [
                'master_start' => '\MasterStart',
                'worker_start' => '\WorkerStart',
            ]
        ],
    ];
}

function getConfig($argument)
{
    $extension = [
        'http' => [
            'httpCoro' => [
                'entrance' => '\\Coroutine::http',
                'setting' => [
                    'log_file' => '/tmp/swoole_http.log',
                    'worker_num' => 1,
                ]
            ],
        ],
        'tcp' => [],
        'process' => [],
        'websocket' => []
    ];

    $configs = configs();

    foreach ($extension as $key => $item) {
        if (strpos($argument, $key) !== false) {
            if (isset($item[$argument])) {
                return array_merge($configs[$key], $item[$argument]);
            } else {
                return $configs[$argument];
            }
        }
    }

    return [];
}