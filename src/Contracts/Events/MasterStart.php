<?php

namespace Swover\Contracts\Events;

interface MasterStart
{
    /**
     * Triggering master start event
     * @param $master_id
     * @return mixed
     */
    public function trigger($master_id);
}