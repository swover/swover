<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Contracts\Request;
use Swover\Contracts\Response;
use Swover\Server;
use Swover\Utils\Event;
use Swover\Worker;

class HttpTest extends TestCase
{
    /**
     * @var \Swoole\Process
     */
    public $process = null;

    /**
     * Create a process as the master process
     *
     * @param \Closure $function
     * @param mixed ...$params
     * @return \Swoole\Process
     */
    public function createProcess(\Closure $function, ...$params)
    {
        ob_flush();
        $this->process = new \Swoole\Process(function (\Swoole\Process $worker) use ($function) {

            $config = call_user_func_array($function, [$worker]);
            $class = new Server($config);
            Event::getInstance()->bindInstance('master_start', 'master_start', function ($server) use ($worker) {
                $worker->write(Worker::getMasterPid());
                $worker->write(Worker::getMasterPid());
                $worker->write(Worker::getMasterPid());
            });
            Event::getInstance()->bindInstance('worker_start', 'worker_start', function ($server, $worker_id) use ($worker) {
                $worker->push($worker_id);
            });
            Event::getInstance()->bindInstance('worker_stop', 'worker_stop', function ($server, $worker_id) use ($worker) {
                $worker->pop();
            });
            ob_flush();
            $class->start();
        });
        $this->process->useQueue();
        $this->process->start();
        while (\swoole_process::wait(false)) {
        }
        $this->process->read(); //Block to wait server startup
        return $this->process;
    }

    /**
     * @after
     */
    public function closeProcess()
    {
        if (is_null($this->process)) return;

        //kill master
        \Swoole\Process::kill($this->process->read(), 15);

        //Block to wait worker stop
        $queue = $this->process->statQueue();
        while ($queue['queue_num'] > 0) {
            usleep(100000);
            $queue = $this->process->statQueue();
        }

        usleep(100000);
        $this->process->close();
        $this->process->freeQueue();
        $this->process = null;
    }

    /**
     * configProvider
     * @return array
     */
    public function configProvider()
    {
        return [[
            'config' => [
                'server_type' => 'http',
                'daemonize' => false,
                'process_name' => 'swover',
                'host' => '127.0.0.1',
                'port' => 9501,
                'worker_num' => 1,
                //'task_worker_num' => 2,
                'max_request' => 5,
                'entrance' => null,
                'async' => false,
                'setting' => [
                    #'log_file' => '/tmp/swoole.log',
                    #'worker_num' => 3,
                ],
                'events' => [
                    #'master_start' => '\MasterStart',
                    #'worker_start' => '\WorkerStart',
                ]
            ]
        ]];
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testStart($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return 'success';
            };
            return $config;
        };

        $this->createProcess($function);

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('success', $this->get($url));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testEvent($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return isset($request->get['name']) ? $request->get['name'] : 'success';
            };
            //Request Event
            Event::getInstance()->bindInstance('request', 'request', function ($server, Request $request) use ($worker) {
                if ($request->get('action', '') == 'test') {
                    //$request->get['name'] = 'ruesin';
                    $request['get']['name'] = 'ruesin';
                }
            });
            //Response Event
            Event::getInstance()->bindInstance('response', 'response', function ($server, Response $response) use ($worker) {
                if ($response['body'] == 'ruesin') {
                    $response->setBody('Hello Xin');
                }
            });
            return $config;
        };

        $this->createProcess($function);

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('Hello Xin', $this->get($url));

        $url = "http://{$config['host']}:{$config['port']}?action=no-test";
        $this->assertEquals('success', $this->get($url));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testPath($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                if ($request->path() == '/user/info') {
                    return 'user:' . $request->get('id', '0');
                }
                if ($request->path() == '/order/info') {
                    return 'order:product' . $request->get('id', '0');
                }
                return 'success';
            };
            return $config;
        };

        $this->createProcess($function);

        $url = "http://{$config['host']}:{$config['port']}/user/info?id=123";
        $this->assertEquals('user:123', $this->get($url));

        $url = "http://{$config['host']}:{$config['port']}/order/info?id=111";
        $this->assertEquals('order:product111', $this->get($url));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testHeader($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return $request->header('auth') ? 'success' : 'failure';
            };
            return $config;
        };

        $this->createProcess($function);

        $headers = ['auth' => 'ruesin'];

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('failure', $this->get($url));

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('success', $this->get($url, $headers));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testCookie($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                if ($request->cookie('name', '') != 'ruesin') {
                    return 'failure';
                }
                return 'success';
            };
            return $config;
        };

        $this->createProcess($function);

        $headers = ['Cookie' => 'name=ruesin;'];

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('failure', $this->get($url));

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('success', $this->get($url, $headers));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testPost($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return $request->post('name') ? $request->post('name') : 'success';
            };
            return $config;
        };

        $this->createProcess($function);

        $data = ['name' => 'ruesin'];

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('success', $this->post($url));

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('ruesin', $this->post($url, $data));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testInput($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return !empty($request->input()) ? $request->input() : 'success';
            };
            return $config;
        };

        $this->createProcess($function);

        $data = "name:ruesin";

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('success', $this->input($url));

        $url = "http://{$config['host']}:{$config['port']}?action=test";
        $this->assertEquals('name:ruesin', $this->input($url, $data));
    }

    public function get($url, $headers = [])
    {
        $header = [];
        foreach ($headers as $key => $value) {
            $header[] = "{$key}:$value";
        }

        $opt = [
            'http' => [
                'method' => 'GET',
                'header' => $header,
            ]
        ];
        return file_get_contents($url, null, stream_context_create($opt));
    }

    public function post($url, $data = [])
    {
        $postdata = http_build_query($data);
        $header = [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '. strlen($postdata)
        ];
        $opt = [
            'http' => [
                'method' => 'POST',
                'header' => $header,
                'content' => $postdata
            ]
        ];
        return file_get_contents($url, null, stream_context_create($opt));
    }

    public function input($url, $data = '')
    {
        $header = [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '. strlen($data)
        ];
        $opt = [
            'http' => [
                'method' => 'POST',
                'header' => $header,
                'content' => $data
            ]
        ];
        return file_get_contents($url, null, stream_context_create($opt));
    }
}