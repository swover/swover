<?php

namespace Swover\Utils;

/**
 * The socket response class
 */
class Response
{
    //server object
    private $server;

    /**
     * http: \swoole_http_response
     * tcp : fd.
     */
    private $resource;

    public function __construct($server, $resource)
    {
        $this->server = $server;
        $this->resource = $resource;
    }

    public function header($key, $value)
    {
        $this->resource->header($key, $value);
    }

    public function status($http_status_code)
    {
        $this->resource->status($http_status_code);
    }

    public function cookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->resource->cookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public function send($data)
    {
        switch ($this->server->server_type) {
            case 'http':
                $this->resource->header('Server', 'swover');
                $this->resource->end($data);
                break;
            default:
                $this->server->send($this->resource, $data);
        }
        return true;
    }
}
