<?php

namespace Swover\Contracts\Events;

interface TaskFinish extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::TASK_FINISH;

    /**
     * Triggering task event
     * @param \Swoole\Server $server
     * @param $task_id
     * @param $data
     * @return mixed
     */
    public function trigger($server, $task_id, $data);
}