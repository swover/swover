<?php

namespace Swover\Utils\Contracts;

interface Request
{
    /**
     * Gets a "parameter" value from any bag.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($key = null, $default = null);

    /**
     * Retrieve a request payload item from the request.
     *
     * @param string $key
     * @param string|array|null $default
     *
     * @return string|array|null
     */
    public function post($key = null, $default = null);

    /**
     * Retrieve input items from the request.
     *
     * @return string|null
     */
    public function input();

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method();

    /**
     * Get the URL (no query string) for the request.
     *
     * @return string
     */
    public function url();

    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl();

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path();

    /**
     * Get the client IP address.
     *
     * @return string|null
     */
    public function ip();
}