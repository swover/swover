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
     * @param $worker_id
     * @return mixed
     */
    public function trigger($worker_id);
}