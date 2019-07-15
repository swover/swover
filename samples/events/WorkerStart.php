<?php

use Swover\Contracts\Events;

class WorkerStart
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = Events::WORKER_START;

    /**
     * @param stdClass|\Swoole\Server $server
     * @param $worker_id
     * @return mixed|void
     */
    public function trigger($server, $worker_id)
    {
        echo 'Worker started ' . $worker_id .PHP_EOL;
    }
}