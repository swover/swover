<?php

namespace Swover\Utils;

/**
 * Request
 */
class Request extends Cache
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
        return [
            'get' => $request->get,
            'post' => $request->post,
            'input' => $request->rawcontent(),
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

    public function get($key, $default = null)
    {
    }

    public function post($key, $default = null)
    {
    }

    public function input()
    {
    }

    public function method()
    {
    }

    /**
     * Get the root URL for the application.
     */
    public function root()
    {
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

    /**
     * Get the client user agent.
     */
    public function userAgent()
    {
    }
}