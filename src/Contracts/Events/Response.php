<?php

namespace Swover\Contracts\Events;

interface Response extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::RESPONSE;

    /**
     * Triggering response event
     * @param \Swover\Utils\Response $response request instance
     * @return mixed
     */
    public function trigger($response);
}