<?php
namespace Swover\Server;

use Swover\Utils\Cache;
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

        $this->server->set([
            'worker_num'      => $this->worker_num,
            'task_worker_num' => $this->task_worker_num,
            'daemonize'       => $this->daemonize,
            'log_file'        => $this->log_file,
            'max_request'     => $this->max_request
        ]);

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
            Worker::setChildStatus(true);
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

        $this->server->on('receive', function ($server, $fd, $from_id, $data) {

            $result = $this->execute($data);

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

            $data = array_merge((array)$request->get, (array)$request->post);

            $result = $this->execute($data);

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