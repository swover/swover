<?php

namespace Swover\Contracts;

interface Event
{
    /**
     * @see \Swover\Contracts\Events\Request
     */
    const REQUEST = 'request';

    /**
     * @see \Swover\Contracts\Events\Response
     */
    const RESPONSE = 'response';

    /**
     * @see \Swoole\Server::$onStart
     */
    const MASTER_START = 'master_start';

    /**
     * @see \Swoole\Server::$onManagerStart
     */
    const MANAGER_START = 'manager_start';

    /**
     * @see \Swoole\Server::$onWorkerStart
     */
    const WORKER_START = 'worker_start';

    /**
     * @see \Swoole\Server::$onWorkerStop
     */
    const WORKER_STOP = 'worker_stop';

    /**
     * @see \Swoole\Server::$onTask
     */
    const TASK_START = 'task_start';

    /**
     * @see \Swoole\Server::$onFinish
     */
    const TASK_FINISH = 'task_finish';

    /**
     * @see \Swoole\Server::$onConnect
     */
    const CONNECT = 'connect';

    /**
     * @see \Swoole\Server::$onClose
     */
    const CLOSE = 'close';
}