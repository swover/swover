<?php
namespace Swover\Server;

use Swover\Utils\Response;
use Swover\Utils\Worker;

/**
 * Socket Server || HTTP Server
 *
 * @property $async Is it asynchronous？
 * @property $signature Need to sign? verify sign function.
 * @property $trace_log output trace log?
 */
class Socket extends Base
{
    //server object
    private $server;

    public function __construct(array $config)
    {
        try {
            parent::__construct($config);

            if (!isset($config['host']) || !isset($config['port'])) {
                die('Has Not Host or Port!' . PHP_EOL);
            }

            if (!is_bool($this->async)) {
                $this->async = boolval($this->async);
            }

            if (!is_bool($this->trace_log)) {
                $this->trace_log = boolval($this->trace_log);
            }

            $this->start($config['host'], $config['port']);
        } catch (\Exception $e) {
            die('Start error: ' . $e->getMessage());
        }
    }

    private function start($host, $port)
    {
        $className = ($this->server_type == 'http') ? '\swoole_http_server' : '\swoole_server';
        $this->server = new $className($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

        $this->server->server_type = $this->server_type;

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
            if ($this->trace_log) {
                $this->log('Receive Data : '.$data);
            }

            $data = json_decode($data, true);

            $resInstance = new Response($this->server, $fd);

            if ($this->verify_sign($data) !== true) {
                return $resInstance->send('no no no~');
            }

            if ($this->async !== true) {
                return $this->event($data, $resInstance);
            }

            $server->task($data);
            return $resInstance->send('success');
        });
        return $this;
    }

    private function onRequest()
    {
        if ($this->server_type !== 'http') return $this;

        $this->server->on('request', function ($request, $response) {

            $data = array_merge((array)$request->get, (array)$request->post);

            if ($this->trace_log) {
                $this->log('Request Data : '.json_encode($data));
            }

            $resInstance = new Response($this->server, $response);

            if ($this->verify_sign($data) !== true) {
                return $resInstance->send('no no no~');
            }

            if ($this->async !== true) {
                return $this->event($data, $resInstance);
            }

            $this->server->task($data);
            return $resInstance->send('success');
        });
        return $this;
    }

    private function onTask()
    {
        $this->server->on('Task', function ($server, $task_id, $src_worker_id, $data)  {
            if ($this->trace_log) {
                $this->log("[#{$server->worker_pid}] Task@[$src_worker_id:$task_id]: Start.");
            }
            $this->event($data);
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

    private function verify_sign($data)
    {
        if (!$this->signature) return true;
        return call_user_func_array($this->signature, [$data]);
    }

    private function event($data, $response = null)
    {
        try {
            $result = $this->entrance($data);
            if ($response != null) {
                $response->send($result ? : 'success');
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}