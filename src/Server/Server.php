<?php

namespace Swover\Server;

use Swover\Contracts\Events;
use Swover\Utils\Request;
use Swover\Utils\Response;
use Swover\Worker;

/**
 * \Swoole\WebSocket\Server | \Swoole\Http\Server | \Swoole\Server
 */
abstract class Server extends Base
{
    /**
     * @var \Swoole\WebSocket\Server | \Swoole\Http\Server | \Swoole\Server
     */
    protected $server;

    abstract protected function getServer($host, $port);

    abstract protected function getCallback();

    protected function start()
    {
        $host = $this->config->get('host', '0.0.0.0');
        $port = $this->config->get('port', 0);

        $this->server = $this->getServer($host, $port);

        $this->config['host'] = $this->server->host;
        $this->config['port'] = $this->server->port;

        $setting = [
            'worker_num' => $this->worker_num,
            'task_worker_num' => max($this->task_worker_num, 0),
            'daemonize' => $this->daemonize,
            'max_request' => $this->max_request
        ];

        $setting = array_merge($setting, $this->config->get('setting', []));

        $this->server->set($setting);

        $callbacks = [
            'onStart',
            'onRequest',
            'onTask',
            'onStop'
        ];
        $callbacks = array_merge($callbacks, $this->getCallback());
        foreach ($callbacks as $callback) {
            $this->$callback();
        }

        $this->server->start();
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
        $this->server->on('Task', function (\Swoole\Server $server, $task_id, $src_worker_id, $data) {
            $this->event->trigger(Events::TASK, $server, $task_id, $src_worker_id, $data);
            $this->entrance($data);
            $server->finish($data);
        });

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