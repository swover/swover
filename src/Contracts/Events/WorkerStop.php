<?php

namespace Swover\Contracts\Events;

interface WorkerStop
{
    /**
     * Triggering worker stop event
     * @param $worker_id
     * @return mixed
     */
    public function trigger($worker_id);
}