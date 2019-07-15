<?php

namespace Swover\Contracts\Events;

interface MasterStart extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::MASTER_START;

    /**
     * Triggering master start event
     * @param \Swoole\Server | \stdClass $server
     * @return mixed
     */
    public function trigger($server);
}