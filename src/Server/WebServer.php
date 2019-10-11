<?php

namespace Swover\Server;

/**
 * WebSocket Server
 */
class WebServer extends Server
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
        $this->server->on('HandShake', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
        });

        $this->server->on('open', function (\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request) {
            echo "server: handshake success with fd{$request->fd}\n";
        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });
    }

    protected function onRequest()
    {
        $this->server->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                return $response->end();
            }
            return $this->execute($this->server, $request)->send($response, $this->server);
        });

        $this->server->on('message', function (\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame) {
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });
    }

}