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
     *
     * @param \Swoole\Server $server
     * @param $task_id
     * @param $worker_id
     * @param $data
     * @return mixed
     */
    public function trigger($server, $task_id, $worker_id, $data);
}