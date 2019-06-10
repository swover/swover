<?php

namespace Swover\Utils;

/**
 * Response
 */
class Response extends Cache implements \Swover\Utils\Contracts\Response
{
    public function __construct()
    {
        $input = [
            'header' => [],
            'status' => 200,
            'cookie' => [],
            'body' => '',
        ];
        parent::__construct($input);
    }

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

        return $server->send($resource, $this->body);
    }

    /**
     * @param $response \Swoole\Http\Response
     * @return mixed
     */
    private function sendHttpResponse($response)
    {
        foreach ($this->header as $key=>$value) {
            $response->header($key, $value);
        }

        foreach ($this->cookie as $cKey=>$cVal) {
            $response->cookie($cKey, $cVal['value'], $cVal['expire'], $cVal['path'], $cVal['domain'], $cVal['secure'], $cVal['httponly']);
        }

        $response->status($this->status);

        return $response->end($this->body);
    }

    public function body($body)
    {
        $this->body = $body;
    }

    public function header($key, $value)
    {
        $this->header[$key] = $value;
    }

    public function status($http_status_code)
    {
        $this->status = $http_status_code;
    }

    public function cookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->cookie[$key] = [
            'value'  => $value,
            'expire' => $expire,
            'path'   => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly
        ];
    }
}