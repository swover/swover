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

        $this->config['host'] = $host;
        $this->config['port'] = $port;

        $setting = [
            'worker_num'      => $this->worker_num,
            'task_worker_num' => max($this->task_worker_num, 0),
            'daemonize'       => $this->daemonize,
            'max_request'     => $this->max_request
        ];

        $setting = array_merge($setting, $this->config->get('setting', []));

        $this->server->set($setting);

        $this->onStart()->onReceive()->onRequest()->onTask()->onStop();

        $this->server->start();
    }

    private function onStart()
    {
        $this->server->on('Start', function (\Swoole\Server $server) {
            $this->event->trigger('master_start', $server->master_pid);
            Worker::setMasterPid($server->master_pid);
            $this->_setProcessName('master');
        });

        $this->server->on('ManagerStart', function(\Swoole\Server $server) {
            $this->_setProcessName('manager');
        });

        $this->server->on('WorkerStart', function (\Swoole\Server $server, $worker_id){
            $str = ($worker_id >= $server->setting['worker_num']) ? 'task' : 'event';
            $this->_setProcessName('worker_'.$str);
            $this->event->trigger('worker_start', $worker_id);
        });

        return $this;
    }

    private function onReceive()
    {
        if ($this->server_type == 'http') return $this;

        $this->server->on('connect', function ($server, $fd, $from_id) {
            $this->event->trigger('connect', $fd);
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

            $result = $this->execute($request);

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
        $this->server->on('Task', function (\Swoole\Server $server, $task_id, $src_worker_id, $data)  {
            $this->event->trigger('task_start', $task_id, $data);
            $this->entrance($data);
            $server->finish($data);
        });
        return $this;
    }

    private function onStop()
    {
        $this->server->on('WorkerStop', function (\Swoole\Server $server, $worker_id){
            $this->event->trigger('worker_stop', $worker_id);
        });
        $this->server->on('Finish', function (\Swoole\Server $server, $task_id, $data) {
            $this->event->trigger('task_finish', $task_id, $data);
        });

        $this->server->on('close', function (\Swoole\Server $server, $fd, $from_id) {
            $this->event->trigger('close', $fd);
        });
    }

    /**
     * @param $data \Swoole\Http\Request|array
     * @return mixed|Response
     */
    protected function execute($data = null)
    {
        $this->event->trigger('request', $data);
        $request = new Request($data);

        //If you want to respond to the client in task, see:
        //https://wiki.swoole.com/wiki/page/925.html
        if (boolval($this->config->get('async', false)) === true) {
            $this->server->task($request);
            $response = new Response();
            $response->setBody('success');
        } else {
            $response = $this->entrance($request);
        }
        $this->event->trigger('response', $response);
        return $response;
    }
}