<?php

namespace Swover\Server;

use Swover\Contracts\Events;
use Swover\Utils\Request;
use Swover\Utils\Response;
use Swover\Worker;

class Tcp extends Base
{
    protected $server_type = 'tcp';

    /**
     * @var \Swoole\WebSocket\Server | \Swoole\Http\Server | \Swoole\Server
     */
    protected $server;

    protected $settings = [];

    protected function getSettings()
    {
        $settings = [
            'worker_num' => $this->worker_num,
            'task_worker_num' => max($this->task_worker_num, 0),
            'daemonize' => $this->daemonize,
            'max_request' => $this->max_request
        ];
        $this->settings = array_merge($settings, $this->settings, $this->config->get('setting', $this->config->get('settings', [])));
        return $this->settings;
    }

    protected function start()
    {
        $this->server = $this->genServer($this->config->get('host', '0.0.0.0'), $this->config->get('port', 0));

        $this->config['host'] = $this->server->host;
        $this->config['port'] = $this->server->port;

        $this->server->set($this->getSettings());

        $callbacks = [
            'onStart',
            'onConnect',
            'onReceive',
            'onTask',
            'onStop',
        ];

        $callbacks = array_merge($callbacks, $this->getCallback());
        foreach ($callbacks as $callback) {
            $this->$callback();
        }

        $this->server->start();
    }

    protected function genServer($host, $port)
    {
        return new \Swoole\Server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
    }

    protected function getCallback()
    {
        return [];
    }

    protected function onConnect()
    {
        $this->server->on('connect', function (\Swoole\Server $server, $fd, $from_id) {
            $this->event->trigger(Events::CONNECT, $server, $fd, $from_id);
        });
    }

    protected function onReceive()
    {
        $this->server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $info = $server->getClientInfo($fd);
            $request = [
                'input' => $data,
                'server' => [
                    'request_time' => $info['connect_time'],
                    'request_time_float' => $info['connect_time'] . '.000',
                    'server_port' => $info['server_port'],
                    'remote_port' => $info['remote_port'],
                    'remote_addr' => $info['remote_ip'],
                    'master_time' => $info["last_time"],
                ]
            ];
            return $this->execute($server, $request)->send($fd, $this->server);
        });
    }

    /**
     * When server startup success, onStart/onManagerStart/onWorkerStart will concurrently in different processes
     * @see https://wiki.swoole.com/wiki/page/41.html
     * @return $this
     */
    protected function onStart()
    {
        $this->server->on('Start', function (\Swoole\Server $server) {
            Worker::setMasterPid($server->master_pid);
            $this->_setProcessName('master');
            $this->event->trigger(Events::START, $server);
        });

        $this->server->on('ManagerStart', function (\Swoole\Server $server) {
            Worker::setMasterPid($server->master_pid);
            $this->_setProcessName('manager');
            $this->event->trigger(Events::MANAGER_START, $server);
        });

        $this->server->on('WorkerStart', function (\Swoole\Server $server, $worker_id) {
            Worker::setMasterPid($server->master_pid);
            $str = ($worker_id >= $server->setting['worker_num']) ? 'task' : 'event';
            $this->_setProcessName('worker_' . $str);
            $this->event->trigger(Events::WORKER_START, $server, $worker_id);
        });

        return $this;
    }

    protected function onTask()
    {
        if ($this->_getSwooleVersion() >= 400020012
            && boolval($this->server->setting['task_enable_coroutine'] ?? false)
        ) {
            $this->server->on('Task', function (\Swoole\Server $server, \Swoole\Server\Task $task) {
                $this->event->trigger(Events::TASK, $server, $task->id, $task->worker_id, $task->data);
                $this->entrance($task->data);
                $task->finish($task->data);
            });
        } else {
            $this->server->on('Task', function (\Swoole\Server $server, $task_id, $src_worker_id, $data) {
                $this->event->trigger(Events::TASK, $server, $task_id, $src_worker_id, $data);
                $this->entrance($data);
                $server->finish($data);
            });
        }

        $this->server->on('PipeMessage', function (\Swoole\Server $server, $src_worker_id, $message) {
            $this->event->trigger(Events::PIPE_MESSAGE, $server, $src_worker_id, $message);
        });
    }

    protected function onStop()
    {
        $this->server->on('ManagerStop', function (\Swoole\Server $server) {
            $this->event->trigger(Events::MANAGER_STOP, $server);
        });
        $this->server->on('WorkerStop', function (\Swoole\Server $server, $worker_id) {
            $this->event->trigger(Events::WORKER_STOP, $server, $worker_id);
        });
        $this->server->on('Finish', function (\Swoole\Server $server, $task_id, $data) {
            $this->event->trigger(Events::FINISH, $server, $task_id, $data);
        });
        $this->server->on('close', function (\Swoole\Server $server, $fd, $from_id) {
            $this->event->trigger(Events::CLOSE, $server, $fd, $from_id);
        });
        $this->server->on('WorkerError', function (\Swoole\Server $server, $worker_id, $worker_pid, $exit_code, $signal) {
            $this->event->trigger(Events::WORKER_ERROR, $server, $worker_id, $worker_pid, $exit_code, $signal);
        });
        if (isset($this->server->setting['reload_async']) && $this->server->setting['reload_async'] === true) {
            $this->server->on('WorkerExit', function (\Swoole\Server $server, $worker_id) {
                $this->event->trigger(Events::WORKER_EXIT, $server, $worker_id);
            });
        }
        $this->server->on('Shutdown', function (\Swoole\Server $server) {
            $this->event->trigger(Events::SHUTDOWN, $server);
        });
    }

    /**
     * @param \Swoole\Server $server
     * @param \Swoole\Http\Request|array $data
     * @return mixed|Response
     */
    protected function execute($server, $data = null)
    {
        $request = new Request($data);
        $this->event->trigger(Events::REQUEST, $server, $request);

        //If you want to respond to the client in task, see:
        //https://wiki.swoole.com/wiki/page/925.html
        if (boolval($this->config->get('async', false)) === true && $this->task_worker_num > 0) {
            $this->server->task($request);
            $response = new Response();
            $response->setBody('success');
        } else {
            $response = $this->entrance($request);
        }
        $this->event->trigger(Events::RESPONSE, $server, $response);
        return $response;
    }
}