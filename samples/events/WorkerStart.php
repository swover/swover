<?php

class WorkerStart
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = 'worker_start';

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