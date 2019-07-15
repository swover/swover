<?php

namespace Swover\Contracts\Events;

interface Close extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::CLOSE;

    /**
     * Triggering close connection event
     * @param \Swoole\Server $server
     * @param mixed $fd Connect's file descriptor
     * @param $from_id
     * @return mixed
     */
    public function trigger($server, $fd, $from_id);
}