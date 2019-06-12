<?php
namespace Swover\Server;

use Swover\Utils\Response;
use Swover\Utils\Worker;

/**
 * Socket Server || HTTP Server
 *
 * @property $async Is it asynchronous？
 * @property $trace_log output trace log?
 */
class Socket extends Base
{
    /**
     * @var \Swoole\Http\Server | \Swoole\Server
     */
    private $server;

    protected function boot()
    {
        if (!isset($this->config['host']) || !isset($this->config['port'])) {
            throw new \Exception('Has Not Host or Port!');
        }

        if (!is_bool($this->async)) {
            $this->async = boolval($this->async);
        }

        if (!is_bool($this->trace_log)) {
            $this->trace_log = boolval($this->trace_log);
        }

        $this->start();
    }

    private function start()
    {
        $className = ($this->server_type == 'http') ? \Swoole\Http\Server::class : \Swoole\Server::class;
        $this->server = new $className($this->config['host'], $this->config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

        $setting = [
            'worker_num'      => $this->worker_num,
            'task_worker_num' => $this->task_worker_num,
            'daemonize'       => $this->daemonize,
            'max_request'     => $this->max_request
        ];

        $setting = array_merge($setting, $this->config['setting']);

        $this->server->set($setting);

        $this->onStart()->onReceive()->onRequest()->onTask()->onStop();

        $this->server->start();
        return $this;
    }

    private function onStart()
    {
        $this->server->on('Start', function ($server) {
            Worker::setMasterPid($server->master_pid);
            $this->_setProcessName('master');
        });

        $this->server->on('ManagerStart', function($server) {
            $this->_setProcessName('manager');
        });

        $this->server->on('WorkerStart', function ($server, $worker_id){
            $str = ($worker_id >= $server->setting['worker_num']) ? 'task' : 'event';
            $this->_setProcessName('worker_'.$str);
            if ($this->trace_log) {
                $this->log("Worker[$worker_id] started.");
            }
        });

        return $this;
    }

    private function onReceive()
    {
        if ($this->server_type == 'http') return $this;

        $this->server->on('connect', function ($server, $fd, $from_id) {
            if ($this->trace_log) {
                $this->log("[#{$server->worker_pid}] Client@[$fd:$from_id]: Connect.");
            }
        });

        $this->server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {

            $info = $server->getClientInfo($fd);

            $data = [
                'input' => $data,
                'server' => [
                    'query_string' => '',
                    'request_method' => 'GET',
                    'request_uri' => '/',
                    'path_info' => '/',
                    'request_time' => $info['connect_time'],
                    'request_time_float' => $info['connect_time'] . '.000',
                    'server_port' => $info['server_port'],
                    'remote_port' => $info['remote_port'],
                    'remote_addr' => $info['remote_ip'],
                    'master_time' => $info["last_time"],
                    //'server_protocol' => 'HTTP/1.1',
                    'server_software' => 'swoole-server'
                ]
            ];

            $result = $this->execute($info);

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

            $result = $this->execute($request);

            return $result->send($response, $this->server);
        });
        return $this;
    }

    private function onTask()
    {
        $this->server->on('Task', function ($server, $task_id, $src_worker_id, $data)  {
            if ($this->trace_log) {
                $this->log("[#{$server->worker_pid}] Task@[$src_worker_id:$task_id]: Start.");
            }
            $this->entrance($data);
            $server->finish($data);
        });
        return $this;
    }

    private function onStop()
    {
        $this->server->on('WorkerStop', function ($server, $worker_id){});
        $this->server->on('Finish', function ($server, $task_id, $data) {
            if ($this->trace_log) {
                $this->log("[#{$server->worker_pid}] Task-$task_id: Finish.");
            }
        });

        $this->server->on('close', function ($server, $fd, $from_id) {
            if ($this->server_type !== 'http') {
                if ($this->trace_log) {
                    $this->log("[#{$server->worker_pid}] Client@[$fd:$from_id]: Close.");
                }
            }
        });
    }

    /**
     * @param $data \Swoole\Http\Request|array
     * @return mixed|Response
     */
    protected function execute($data = null)
    {
        if ($this->trace_log) {
            $this->log('Request Data : '.json_encode($data));
        }

        if ($this->async === true) {
            $this->server->task($data);
            //TODO 异步测试
            $response = new Response();
            $response->body('success');
        } else {
            $response = $this->entrance($data);
        }
        return $response;
    }
}