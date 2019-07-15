<?php

namespace Swover\Contracts\Events;

interface Connect extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::CONNECT;

    /**
     * Triggering connect event
     *
     * @param \Swoole\Server $server
     * @param $fd Connect's file descriptor
     * @param $from_id
     * @return mixed
     */
    public function trigger($server, $fd, $from_id);
}