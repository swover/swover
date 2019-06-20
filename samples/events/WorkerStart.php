<?php

class WorkerStart implements \Swover\Contracts\Events\WorkerStart
{
    /**
     * Triggering Master start event
     * @param $worker_id
     * @return mixed
     */
    public function trigger($worker_id)
    {
        echo 'Worker started ' . $worker_id .PHP_EOL;
    }
}