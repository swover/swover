<?php

namespace Swover\Contracts\Events;

use Swover\Contracts\Events;

interface Request extends Events
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::REQUEST;

    /**
     * Triggering request or receive event
     * @param \Swoole\Server | \stdClass $server
     * @param \Swoole\Http\Request request data
     * @return mixed
     */
    public function trigger($server, $request);
}