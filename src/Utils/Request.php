<?php

namespace Swover\Utils;

/**
 * Request
 */
class Request extends ArrayObject implements \Swover\Contracts\Request
{
    /**
     * Request constructor.
     * @param $input \Swoole\Http\Request | array
     */
    public function __construct($input)
    {
        if (is_array($input)) {
            $input = $this->initArray($input);
        }

        if ($input instanceof \Swoole\Http\Request) {
            $input = $this->initHttp($input);
        }

        parent::__construct($input);
    }

    private function initHttp(\Swoole\Http\Request $request)
    {
        try {
            $input = $request->rawcontent();
        } catch (\Exception $e) {
            //Swoole\Http\Request::rawcontent(): Http request is finished.
            $input = null; //TODO
        }
        return [
            'get' => $request->get,
            'post' => $request->post,
            'input' => $input,
            'header' => $request->header,
            'server' => $request->server
        ];
    }

    private function initArray(array $input)
    {
        return [
            'get' => isset($input['get']) ? $input['get'] : [],
            'post' => isset($input['post']) ? $input['post'] : [],
            'input' => isset($input['input']) ? $input['input'] : [],
            'header' => isset($input['header']) ? $input['header'] : [],
            'server' => isset($input['server']) ? $input['server'] : [],
        ];
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

    public function input()
    {
        return isset($this->input) ? $this->input : null;
    }

    public function method()
    {
        return strtoupper(isset($this->server['method']) ? $this->server['method'] :
            (isset($this->server['request_method']) ? $this->server['request_method'] : 'get'));
    }

    /**
     * Get the URL (no query string) for the request.
     */
    public function url()
    {
    }

    /**
     * Get the full URL for the request.
     */
    public function fullUrl()
    {
    }

    /**
     * Get the current path info for the request.
     */
    public function path()
    {
    }

    /**
     * Get the client IP address.
     */
    public function ip()
    {
    }
}