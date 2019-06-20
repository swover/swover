<?php

namespace Swover\Contracts\Events;

interface WorkerStart
{
    /**
     * Triggering worker start event
     * @param $worker_id
     * @return mixed
     */
    public function trigger($worker_id);
}