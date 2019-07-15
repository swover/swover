<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Contracts\Response;
use Swover\Server;
use Swover\Utils\Event;
use Swover\Worker;

class ProcessTest extends TestCase
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
    public function callProcess(\Closure $function, ...$params)
    {
        ob_flush();
        $this->process = new \Swoole\Process(function (\Swoole\Process $worker) use ($function) {
            $config = call_user_func_array($function, [$worker]);
            $class = new Server($config);
            ob_flush();
            $class->start();
        });
        $this->process->start();
        while (\swoole_process::wait(true)) {
        }
        return $this->process;
    }

    /**
     * @after
     */
    public function closeProcess()
    {
        if (is_null($this->process)) return;
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
                'server_type' => 'process',
                'daemonize' => false,
                'process_name' => 'swover',
                'worker_num' => 1,
                'max_request' => 5,
                'entrance' => null,
                'events' => []
            ]
        ]];
    }

    /**
     * Start process, random stop in entrance
     *
     * @dataProvider configProvider
     * @param array $config
     */
    public function testStart($config)
    {
        $config['max_request'] = 0;
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function ($request) {
                usleep(100000);
                if (mt_rand(1, 10) == 5) {
                    \Swoole\Process::kill(Worker::getMasterPid(), 9);
                }
            };
            Event::getInstance()->bindInstance('worker_stop', 'stop', function ($server, $worker_id) use ($worker) {
                $worker->write('byebye');
            });
            return $config;
        };
        $process = $this->callProcess($function);

        $this->assertEquals('byebye', $process->read());
    }

    /**
     * Stop worker when run N
     *
     * @dataProvider configProvider
     * @param array $config
     */
    public function testWorkerStop($config)
    {
        $config['worker_num'] = 5;
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function ($request) {
                usleep(100000);
            };
            Event::getInstance()->bindInstance('worker_stop', 'shutdown_stop', function ($server, $worker_id) use ($worker) {
                if ($worker_id === 0) {
                    sleep(1);
                    $worker->write('worker_stop_bye');
                    \Swoole\Process::kill(Worker::getMasterPid(), 9);
                } else {
                    while (\Swoole\Process::kill(Worker::getMasterPid(), 0)) {
                        sleep(1);
                        continue;
                    }
                }
            });
            return $config;
        };
        $process = $this->callProcess($function);

        $this->assertEquals('worker_stop_bye', $process->read());
    }

    /**
     * Stop process
     *
     * @dataProvider configProvider
     * @param array $config
     */
    public function testStop($config)
    {
        $config['max_request'] = 0;
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function ($request) use ($config) {
                usleep(100000);
                if (mt_rand(1, 10) == 5) {
                    if (PHP_OS == 'Darwin') {
                        \Swoole\Process::kill(Worker::getMasterPid(), 9);
                    } else {
                        $class = new Server($config);
                        //$class->stop();
                        $class->force();
                    }
                }
            };
            Event::getInstance()->bindInstance('worker_stop', 'stop', function ($server, $worker_id) use ($worker) {
                #$worker->write('byebye');
            });
            return $config;
        };
        $process = $this->callProcess($function);
        #$this->assertEquals('byebye', $process->read());
    }

    /**
     * Handling Request
     *
     * @dataProvider configProvider
     * @param array $config
     */
    public function testRequest($config)
    {
        $config['max_request'] = 1;
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function ($request) use ($config, $worker) {
                usleep(100000);
                $worker->write(isset($request['say']) ? $request['say'] : 'no-say');
            };
            Event::getInstance()->bindInstance('request', 'request', function ($server, $request) {
                $request['say'] = 'bye-bye';
            });
            Event::getInstance()->bindInstance('worker_stop', 'stop', function ($server, $worker_id) use ($worker) {
                \Swoole\Process::kill(Worker::getMasterPid(), 9);
            });
            return $config;
        };
        $process = $this->callProcess($function);
        $this->assertEquals('bye-bye', $process->read());
    }

    /**
     * Handling Response
     *
     * @dataProvider configProvider
     * @param array $config
     */
    public function testResponse($config)
    {
        $config['max_request'] = 0;
        $function = function (\Swoole\Process $worker) use ($config) {
            $config['entrance'] = function ($request) use ($config, $worker) {
                usleep(100000);
                if (mt_rand(1, 10) === 5) {
                    return 'stop';
                }
                if (mt_rand(1, 10) === 6) {
                    return false;
                }
                return true;
            };
            Event::getInstance()->bindInstance('response', 'response', function ($server, Response $response) use ($worker) {
                if ($response->body == 'stop') {
                    $response->setCode(400);
                }
                if ($response->code == 500) {
                    $response->setBody('stop');
                }
                if ($response->code > 200) {
                    $worker->write($response->body . 'code' . $response->code);
                }
            });

            Event::getInstance()->bindInstance('worker_stop', 'stop', function ($server, $worker_id) use ($worker) {
                \Swoole\Process::kill(Worker::getMasterPid(), 9);
            });
            return $config;
        };
        $process = $this->callProcess($function);
        $message = $process->read();
        $this->assertRegExp('/(stopcode400|stopcode500)/', $message);
    }

    /**
     * Restart worker
     *
     * @dataProvider configProvider
     * @param array $config
     */
    public function testRestart($config)
    {
        $config['max_request'] = 1;
        $config['worker_num']  = 3;
        $function = function (\Swoole\Process $worker) use ($config) {

            $config['entrance'] = function ($request) use ($config, $worker) {
                usleep(100000);
            };

            $worker->useQueue();

            Event::getInstance()->bindInstance('worker_start', 'worker_start', function ($server, $request) use ($worker) {
                $worker->push(1);
            });

            Event::getInstance()->bindInstance('worker_stop', 'stop', function ($server, $worker_id) use ($worker, $config) {
                $stat = $worker->statQueue();
                if ($worker_id == 0 && $stat['queue_num'] > $config['worker_num'] * 2 ) {
                    $worker->write($stat['queue_num']);
                    \Swoole\Process::kill(Worker::getMasterPid(), 9);
                }
            });
            return $config;
        };
        $process = $this->callProcess($function);
        $message = $process->read();
        $this->assertGreaterThan($config['worker_num'], $message);
    }
}
