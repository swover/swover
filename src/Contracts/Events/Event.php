<?php

namespace Swover\Contracts\Events;

interface Event
{
    const MASTER_START = 'master_start';
    const MANAGER_START = 'manager_start';

    const WORKER_START = 'worker_start';
    const WORKER_STOP = 'worker_stop';

    const TASK_START = 'task_start';
    const TASK_FINISH = 'task_finish';

    const CONNECT = 'connect';
    const CLOSE = 'close';

    const REQUEST = 'request';
    const RESPONSE = 'response';
}