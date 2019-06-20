<?php

namespace Swover\Contracts\Events;

interface Close
{
    /**
     * Triggering close connection event
     * @param mixed $fd Connect's file descriptor
     * @return mixed
     */
    public function trigger($fd);
}