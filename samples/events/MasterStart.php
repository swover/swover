<?php

class MasterStart implements \Swover\Contracts\Events\MasterStart
{
    /**
     * @param stdClass|\Swoole\Server $server
     * @return mixed|void
     */
    public function trigger($server)
    {
        echo 'Master started ' . $server->master_pid . PHP_EOL;
    }
}