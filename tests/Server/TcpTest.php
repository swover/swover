<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Contracts\Request;
use Swover\Contracts\Response;
use Swover\Server;
use Swover\Utils\Event;
use Swover\Worker;

class TcpTest extends TestCase
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
                'server_type' => 'tcp',
                'daemonize' => false,
                'process_name' => 'swover',
                'host' => '127.0.0.1',
                'port' => 9502,
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
                $input = $request->input();
                if (!$input) return 'failure';
                parse_str($input, $get);
                if (empty($get)) return 'failure';
                return isset($get['data']['id']) ? 'id' . $get['data']['id'] : 'success';
            };
            return $config;
        };

        $this->createProcess($function);

        #$this->assertEquals('failure', $this->client($config));

        $data = ['action' => 'test'];
        $this->assertEquals('success', $this->client($config, $data));

        $id = mt_rand(100, 200);
        $data['data'] = ['id' => $id];
        $this->assertEquals('id' . $id, $this->client($config, $data));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testEvent($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return $request->header('auth', '') ? $request->header('auth') : 'success';
            };
            //Request Event
            Event::getInstance()->bindInstance('request', 'request', function ($server, Request $request) use ($worker) {
                parse_str($request->input(), $get);
                if (isset($get['action']) && $get['action'] == 'user') {
                    $request['header']['auth'] = 'ruesin';
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

        $data = ['action' => 'test'];
        $this->assertEquals('success', $this->client($config, $data));

        $data['action'] = 'user';
        $this->assertEquals('Hello Xin', $this->client($config, $data));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testMethod($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return $request->method();
            };
            return $config;
        };

        $this->createProcess($function);

        $data = ['action' => 'test'];
        $this->assertEquals('GET', $this->client($config, $data));
    }

    /**
     * @dataProvider configProvider
     * @param array $config
     */
    public function testPath($config)
    {
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function (Request $request) use ($worker) {
                return $request->path();
            };
            return $config;
        };

        $this->createProcess($function);

        $data = ['action' => 'test'];
        $this->assertEquals('/', $this->client($config, $data));
    }

    public function client($config, $data = [])
    {
        $client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $client->connect($config['host'], $config['port'], -1);
        $client->send(http_build_query($data));
        $result = $client->recv();
        $client->close();
        return $result;
    }
}