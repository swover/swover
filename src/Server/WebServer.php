<?php

namespace Swover\Server;

use Swover\Contracts\Events;

class WebServer extends Http
{
    protected $server_type = 'websocket';

    protected function genServer($host, $port)
    {
        return new \Swoole\WebSocket\Server($host, $port);
    }

    protected function getCallback()
    {
        return [
            'onOpen'
        ];
    }

    protected function onOpen()
    {
        $this->server->on('open', function (\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request) {
            $this->event->trigger(Events::WEBSOCKET_OPEN, $server, $request);
        });
    }

    protected function onReceive()
    {
        $this->server->on('message', function (\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame) {
            $info = $server->getClientInfo($frame->fd);
            $request = [
                'input' => $frame->data,
                'server' => [
                    'opcode' => $frame->opcode,
                    'request_time' => $info['connect_time'],
                    'request_time_float' => $info['connect_time'] . '.000',
                    'server_port' => $info['server_port'],
                    'remote_port' => $info['remote_port'],
                    'remote_addr' => $info['remote_ip'],
                    'master_time' => $info["last_time"],
                ]
            ];
            $server->push($frame->fd, $this->execute($this->server, $request)->body);
        });
    }

    public static function onHandShake(\Swoole\WebSocket\Server $server)
    {
        $server->on('HandShake', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
            $this->event->trigger(Events::HAND_SHAKE, $request, $response);
        });
    }

    public static function onRequest(\Swoole\WebSocket\Server $server)
    {
        $server->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                return $response->end();
            }
            return $this->execute($this->server, $request)->send($response, $this->server);
        });
    }
}