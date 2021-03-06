<?php

namespace Swover\Contracts;

interface Response
{
    /**
     * Send response data to client
     *
     * @param mixed | \Swoole\Http\Response $resource
     * @param \Swoole\Http\Server | \Swoole\Server $server
     * @return bool
     */
    public function send($resource, $server);

    /**
     * Set response body content
     *
     * @param string $body
     * @return mixed
     */
    public function setBody($body);

    /**
     * Set response headers
     *
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function setHeader($key, $value);

    /**
     * Set response http code
     *
     * @param int $status_code
     * @return mixed
     */
    public function setCode($status_code);

    /**
     * Set response cookie
     * @param string $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return mixed
     */
    public function setCookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false);
}