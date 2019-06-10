<?php

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

require_once dirname(__DIR__) . '/libs/Entrance.php';

require_once dirname(__DIR__) . '/libs/process.php';

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
            #'log_file' => '/Users/sin/swoole.log',
            // 'entrance' => '\\Entrance::process',
            'entrance' => 'singleProcess',
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
            #'log_file' => '/tmp/swoole_http.log',
            'entrance' => '\\Entrance::http',
            'async' => false,
            'trace_log' => true,
            'setting' => [
                'log_file' => '/Users/sin/swoole_http.log',
                'worker_num' => 3,
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
            #'log_file' => '/Users/sin/swoole_tcp.log',
            'entrance' => '\\Entrance::tcp',
            'async' => false,
            'trace_log' => true
        ]
    ];
}

function getConfig($argument)
{
    $extension = [
        'http' => [
            'httpGet' => [
                'entrance' => '\\Entrance::httpGet',
            ],
            'httpPost' => [
                'entrance' => '\\Entrance::httpPost',
            ],
            'httpInput' => [
                'entrance' => '\\Entrance::httpInput',
            ],
        ],
        'tcp' => [],
        'process' => [],
    ];

    $configs = configs();

    foreach ($extension as $key=>$item) {
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