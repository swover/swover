<?php

namespace Swover\Contracts\Events;

interface TaskStart extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::TASK_START;

    /**
     * Triggering task event
     * @param $task_id
     * @param $data
     * @return mixed
     */
    public function trigger($task_id, $data);
}