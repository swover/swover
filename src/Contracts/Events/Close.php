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
     * @param mixed $fd Connect's file descriptor
     * @return mixed
     */
    public function trigger($fd);
}