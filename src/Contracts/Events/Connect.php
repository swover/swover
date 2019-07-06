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
     * @param mixed $fd Connect's file descriptor
     * @return mixed
     */
    public function trigger($fd);
}