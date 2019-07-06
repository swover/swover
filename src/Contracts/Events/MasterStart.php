<?php

namespace Swover\Contracts\Events;

interface MasterStart extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::MASTER_START;

    /**
     * Triggering master start event
     * @param $master_id
     * @return mixed
     */
    public function trigger($master_id);
}