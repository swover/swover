<?php

namespace Swover\Server;

/**
 * Http Server
 */
class Http extends Server
{
    protected $server_type = 'http';

    protected function getServer($host, $port)
    {
        return new \Swoole\Http\Server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
    }

    protected function getCallback()
    {
        return [];
    }

    protected function onRequest()
    {
        $this->server->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                return $response->end();
            }
            return $this->execute($this->server, $request)->send($response, $this->server);
        });
    }
}