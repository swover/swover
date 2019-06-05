<?php

namespace Swover\Utils;

/**
 * Response
 */
class Response extends Cache
{
    protected static $instance = null;

    /**
     * @param $resource mixed | \Swoole\Http\Response
     * @param $server \Swoole\Http\Server | \Swoole\Server
     * @return bool
     */
    public function send($resource, $server)
    {
        if (!$server instanceof \Swoole\Server) {
            return false;
        }

        if ($resource instanceof \Swoole\Http\Response) {
            return $this->sendHttpResponse($resource);
        }

        return $server->send($resource, $this['body']);
    }

    /**
     * @param $response \Swoole\Http\Response
     * @return mixed
     */
    private function sendHttpResponse($response)
    {
        $this->build();

        foreach ($this['header'] as $key=>$value) {
            $response->header($key, $value);
        }
        foreach ($this['cookie'] as $cKey=>$cVal) {
            $response->cookie($cKey, $cVal['value'], $cVal['expire'], $cVal['path'], $cVal['domain'], $cVal['secure'], $cVal['httponly']);
        }

        $response->status($this['status']);

        return $response->end($this['body']);
    }

    private function build()
    {
        if (!isset($this['header']) || !is_array($this['header'])) {
            $this['header'] = [];
        }

        if (!isset($this['body'])) {
            $this['body'] = '';
        }

        if (!isset($this['status'])) {
            $this['status'] = 200;
        }

        if (!isset($this['cookie'])) {
            $this['cookie'] = [];
        }
    }

    public function body($body)
    {
        $this['body'] = $body;
    }

    public function header($key, $value)
    {
        if (!isset($this['header'])) {
            $this['header'] = [];
        }
        $this['header'][$key] = $value;
    }

    public function status($http_status_code)
    {
        $this['status'] = $http_status_code;
    }

    public function cookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (!isset($this['cookie'])) {
            $this['cookie'] = [];
        }
        $this['cookie'][$key] = [
            'value'  => $value,
            'expire' => $expire,
            'path'   => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly
        ];
    }
}