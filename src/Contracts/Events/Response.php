<?php

namespace Swover\Contracts\Events;

interface Response
{
    /**
     * Triggering response event
     * @param \Swover\Utils\Response $response request instance
     * @return mixed
     */
    public function trigger($response);
}