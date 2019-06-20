<?php

namespace Swover\Contracts\Events;

interface Connect
{
    /**
     * Triggering connect event
     * @param mixed $fd Connect's file descriptor
     * @return mixed
     */
    public function trigger($fd);
}