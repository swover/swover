<?php

namespace Swover\Contracts\Events;

interface WorkerStop extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::WORKER_STOP;

    /**
     * Triggering worker stop event
     * @param $worker_id
     * @return mixed
     */
    public function trigger($worker_id);
}