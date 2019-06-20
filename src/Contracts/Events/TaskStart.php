<?php

namespace Swover\Contracts\Events;

interface TaskStart
{
    /**
     * Triggering task event
     * @param $task_id
     * @param $data
     * @return mixed
     */
    public function trigger($task_id, $data);
}