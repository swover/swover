<?php

class WorkerStart implements \Swover\Contracts\Events\WorkerStart
{
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