<?php

namespace Swover\Contracts;

/**
 * Defines Events
 *
 * @see https://wiki.swoole.com/wiki/page/41.html
 */
interface Events
{
    /**
     * @see \Swover\Contracts\Events\Request
     * @see \Swoole\Server::$onReceive
     * @see \Swoole\Http\Server::$onRequest
     */
    const REQUEST = 'request';

    /**
     * @see \Swover\Contracts\Events\Response
     */
    const RESPONSE = 'response';

    /**
     * @see \Swoole\Server::$onStart
     */
    const MASTER_START = 'start';
    const START = self::MASTER_START;

    /**
     * @see \Swoole\Server::$onShutdown
     */
    const SHUTDOWN = 'shutdown';

    /**
     * @see \Swoole\Server::$onManagerStart
     */
    const MANAGER_START = 'managerStart';

    /**
     * @see \Swoole\Server::$onManagerStop
     */
    const MANAGER_STOP = 'managerStop';

    /**
     * @see \Swoole\Server::$onWorkerStart
     */
    const WORKER_START = 'workerStart';

    /**
     * @see \Swoole\Server::$onWorkerStop
     */
    const WORKER_STOP = 'workerStop';

    /**
     * @see \Swoole\Server::$onWorkerExit
     */
    const WORKER_EXIT = 'workerExit';

    /**
     * @see \Swoole\Server::$onWorkerError
     */
    const WORKER_ERROR = 'workerError';

    /**
     * @see \Swoole\Server::$onTask
     */
    const TASK_START = 'task';
    const TASK = self::TASK_START;

    /**
     * @see \Swoole\Server::$onFinish
     */
    const TASK_FINISH = 'finish';

    /**
     * @see \Swoole\Server::$onConnect
     */
    const CONNECT = 'connect';

    /**
     * @see \Swoole\Server::$onClose
     */
    const CLOSE = 'close';

    /**
     * @see \Swoole\Server::$onClose
     */
    const PACKET = 'packet';

    /**
     * @see \Swoole\Server::$onClose
     */
    const PIPE_MESSAGE = 'pipeMessage';
}