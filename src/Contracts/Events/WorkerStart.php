<?php

namespace Swover\Contracts\Events;

interface WorkerStart extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::WORKER_START;

    /**
     * Triggering worker start event
     * @param \Swoole\Server | \stdClass $server
     * @param $worker_id
     * @return mixed
     */
    public function trigger($server, $worker_id);
}