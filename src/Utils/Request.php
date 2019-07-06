<?php

namespace Swover\Utils;

/**
 * Request
 */
class Request extends ArrayObject implements \Swover\Contracts\Request
{
    /**
     * Request constructor.
     * @param $request \Swoole\Http\Request | array
     */
    public function __construct($request)
    {
        $input = $this->initRequest($request);
        parent::__construct($input);
    }

    private function initRequest($request)
    {
        $input = [];

        if (is_array($request)) {
            $input = $this->initArray($request);
        }

        if ($request instanceof \Swoole\Http\Request) {
            $input = $this->initHttp($request);
        }


        if (empty($input)) return [];

        $input['request'] = array_merge((array)$input['get'], (array)$input['post']);

        if (!empty($input['post'])) {
            $input['server']['method'] = 'POST';
        }

        return $input;
    }

    private function initHttp(\Swoole\Http\Request $request)
    {
        //Swoole\Http\Request::rawcontent(): Http request is finished.
        $result = [
            'get' => isset($request->get) ? $request->get : [],
            'post' => isset($request->post) ? $request->post : [],
            'input' => @$request->rawcontent(),
            'header' => isset($request->header) ? $request->header : [],
            'server' => isset($request->server) ? $request->server : [],
            'cookie' => isset($request->cookie) ? $request->cookie : [],
        ];

        //application/x-www-form-urlencoded
        if ($result['input'] === false && !empty($result['post'])) {
            $result['input'] = urldecode(http_build_query($result['post']));
            if (count($result['post']) == 1 && current($result['post']) === '') {
                $result['input'] = rtrim($result['input'], '=');
            }
        }

        return $result;
    }

    private function initArray(array $input)
    {
        $default = [
            'server' => [
                'query_string' => '',
                'request_method' => 'GET',
                'request_uri' => '/',
                'path_info' => '/',
                //'server_protocol' => 'HTTP/1.1',
                'server_software' => 'swoole-server'
            ]
        ];

        $result = ['get'=>[],'post'=>[],'input'=>[],'header'=>[],'server'=>[],'cookie'=>[]];

        foreach ($input as $key=>$value) {
            $key = trim(strtolower($key));
            if (!isset($result[$key])) continue;
            $result[$key] = $value;
        }

        $result['server'] = array_merge($default['server'], $result['server']);
        return $result;
    }

    public function get($key = null, $default = null)
    {
        if (is_null($key)) return $this->get;
        return isset($this->get[$key]) ? $this->get[$key] : $default;
    }

    public function post($key = null, $default = null)
    {
        if (is_null($key)) return $this->post;
        return isset($this->post[$key]) ? $this->post[$key] : $default;
    }

    public function request($key = null, $default = null)
    {
        if (is_null($key)) return $this->request;
        return isset($this->request[$key]) ? $this->request[$key] : $default;
    }

    public function input()
    {
        return isset($this->input) ? $this->input : null;
    }

    public function method()
    {
        return strtoupper(isset($this->server['method']) ? $this->server['method'] :
            (isset($this->server['request_method']) ? $this->server['request_method'] : 'get'));
    }

    public function url()
    {
        return $this->server['request_uri'];
    }

    public function path()
    {
        return $this->server['path_info'];
    }

    public function ip()
    {
        return isset($this->server['remote_addr']) ? $this->server['remote_addr'] : '';
    }

    public function cookie($key = null, $default = null)
    {
        if (is_null($key)) return $this->cookie;
        return isset($this->cookie[$key]) ? $this->cookie[$key] : $default;
    }
}