<?php

namespace Swover\Server;

use Swover\Utils\Request;
use Swover\Utils\Response;
use Swover\Worker;

/**
 * Socket Server || HTTP Server
 */
class Socket extends Base
{
    /**
     * @var \Swoole\Http\Server | \Swoole\Server
     */
    private $server;

    protected function start()
    {
        $host = $this->config->get('host', '0.0.0.0');
        $port = $this->config->get('port', 0);

        $className = ($this->server_type == 'http') ? \Swoole\Http\Server::class : \Swoole\Server::class;
        $this->server = new $className($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

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

        $this->onStart()->onReceive()->onRequest()->onTask()->onStop();

        $this->server->start();
    }

    /**
     * When server startup success, onStart/onManagerStart/onWorkerStart will concurrently in different processes
     * @see https://wiki.swoole.com/wiki/page/41.html
     * @return $this
     */
    private function onStart()
    {
        $this->server->on('Start', function (\Swoole\Server $server) {
            Worker::setMasterPid($server->master_pid);
            $this->_setProcessName('master');
            $this->event->trigger('master_start', $server);
        });

        $this->server->on('ManagerStart', function (\Swoole\Server $server) {
            Worker::setMasterPid($server->master_pid);
            $this->_setProcessName('manager');
        });

        $this->server->on('WorkerStart', function (\Swoole\Server $server, $worker_id) {
            Worker::setMasterPid($server->master_pid);
            $str = ($worker_id >= $server->setting['worker_num']) ? 'task' : 'event';
            $this->_setProcessName('worker_' . $str);
            $this->event->trigger('worker_start', $server, $worker_id);
        });

        return $this;
    }

    private function onReceive()
    {
        if ($this->server_type == 'http') return $this;

        $this->server->on('connect', function (\Swoole\Server $server, $fd, $from_id) {
            $this->event->trigger('connect', $server, $fd, $from_id);
        });

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

            $result = $this->execute($server, $request);

            return $result->send($fd, $this->server);
        });
        return $this;
    }

    private function onRequest()
    {
        if ($this->server_type !== 'http') return $this;

        $this->server->on('request', function ($request, $response) {

            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                return $response->end();
            }

            $result = $this->execute($this->server, $request);

            return $result->send($response, $this->server);
        });
        return $this;
    }

    private function onTask()
    {
        $this->server->on('Task', function (\Swoole\Server $server, $task_id, $src_worker_id, $data) {
            $this->event->trigger('task_start', $server, $task_id, $src_worker_id, $data);
            $this->entrance($data);
            $server->finish($data);
        });
        return $this;
    }

    private function onStop()
    {
        $this->server->on('WorkerStop', function (\Swoole\Server $server, $worker_id) {
            $this->event->trigger('worker_stop', $server, $worker_id);
        });
        $this->server->on('Finish', function (\Swoole\Server $server, $task_id, $data) {
            $this->event->trigger('task_finish', $server, $task_id, $data);
        });
        $this->server->on('close', function (\Swoole\Server $server, $fd, $from_id) {
            $this->event->trigger('close', $server, $fd, $from_id);
        });
        $this->server->on('WorkerError', function (\Swoole\Server $server, $worker_id, $worker_pid, $exit_code, $signal) {
            $this->event->trigger('worker_error', $server, $worker_id, $worker_pid, $exit_code, $signal);
        });
        $this->server->on('Shutdown', function (\Swoole\Server $server) {
            $this->event->trigger('shutdown', $server);
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
        $this->event->trigger('request', $server, $request);

        //If you want to respond to the client in task, see:
        //https://wiki.swoole.com/wiki/page/925.html
        if (boolval($this->config->get('async', false)) === true) {
            $this->server->task($request);
            $response = new Response();
            $response->setBody('success');
        } else {
            $response = $this->entrance($request);
        }
        $this->event->trigger('response', $server, $response);
        return $response;
    }
}