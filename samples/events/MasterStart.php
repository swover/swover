<?php

class MasterStart implements \Swover\Contracts\Events\MasterStart
{
    /**
     * Triggering Master start event
     * @param $master_id
     * @return mixed
     */
    public function trigger($master_id)
    {
        echo 'Master started ' . $master_id . PHP_EOL;
    }
}