<?php

namespace Swover\Contracts\Events;

use Swover\Contracts\Event;

interface Response extends Event
{
    /**
     * The event-type for bounds
     */
    const EVENT_TYPE = self::RESPONSE;

    /**
     * Triggering response event
     * @param \Swoole\Server | \stdClass $server
     * @param \Swover\Utils\Response $response request instance
     * @return mixed
     */
    public function trigger($server, $response);
}