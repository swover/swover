<?php

namespace Swover\Contracts\Events;

interface Request
{
    /**
     * Triggering request or receive event
     * @param \Swoole\Http\Request|array $request request data
     * @return mixed
     */
    public function trigger($request);
}