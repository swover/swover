<?php

use Swover\Contracts\Events;

class MasterStart
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = Events::MASTER_START;

    /**
     * @param stdClass|\Swoole\Server $server
     * @return mixed|void
     */
    public function trigger($server)
    {
        echo 'Master started ' . $server->master_pid . PHP_EOL;
    }
}