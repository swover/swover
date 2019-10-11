<?php

namespace Swover\Server;

use Swover\Contracts\Events;

/**
 * Tcp Server
 */
class Tcp extends Server
{
    protected $server_type = 'tcp';

    protected function genServer($host, $port)
    {
        return new \Swoole\Server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
    }

    protected function getCallback()
    {
        return [
            'onConnect'
        ];
    }

    protected function onConnect()
    {
        $this->server->on('connect', function (\Swoole\Server $server, $fd, $from_id) {
            $this->event->trigger(Events::CONNECT, $server, $fd, $from_id);
        });
    }

    protected function onRequest()
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
}