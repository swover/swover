<?php

namespace Swover\Server;

use Swover\Utils\Request;
use Swover\Worker;

/**
 * Process Server
 */
class Process extends Base
{
    /**
     * workers array, key is worker's process_id, value is array
     * [
     *     'id' => $worker_id,
     *     'process' => \Swoole\Process
     * ]
     * @var array
     */
    private $workers = [];

    protected function start()
    {
        if (!extension_loaded('pcntl')) {
            throw new \Exception('Process required pcntl-extension!');
        }

        if ($this->daemonize === true) {
            \Swoole\Process::daemon(true, false);
        }

        $this->MasterStart();

        for ($worker_id = 0; $worker_id < $this->worker_num; $worker_id++) {
            $this->WorkerStart($worker_id);
        }

        $this->asyncProcessWait();
    }

    private function MasterStart()
    {
        $this->event->trigger('master_start', posix_getpid());
        Worker::setMasterPid(posix_getpid());
        $this->_setProcessName('master');
    }

    private function WorkerStart($worker_id)
    {
        $process = new \Swoole\Process(function (\Swoole\Process $worker) use ($worker_id) {

            $this->_setProcessName('worker_' . $worker_id);
            $this->event->trigger('worker_start', $worker_id);

            Worker::setStatus(true);

            pcntl_signal(SIGUSR1, function ($signo) {
                Worker::setStatus(false);
            });

            $this->execute();

            $this->event->trigger('worker_stop', $worker_id);

            $worker->exit(0);
        }, $this->daemonize);

        $pid = $process->start();

        swoole_event_add($process->pipe, function ($pipe) use ($process) {
            if ($message = $process->read()) {
                if ($log_file = $this->config->get('log_file', '')) {
                    error_log(date('Y-m-d H:i:s') . ' ' . ltrim($message) . PHP_EOL, 3, $log_file);
                } else {
                    echo trim($message) . PHP_EOL;
                }
            }
        });

        $this->workers[$pid] = [
            'id' => $worker_id,
            'process' => $process
        ];
        return $pid;
    }

    protected function execute($data = null)
    {
        $signal = 0;
        for ($i = ($this->max_request <= 0 ? $this->max_request - 1 : $this->max_request);
             $i != 0; $i--) {

            $signal = $this->getProcessSignal();
            if ($signal > 0) {
                break;
            }

            try {
                $this->event->trigger('request', []);
                $response = $this->entrance(new Request([]));
                $this->event->trigger('response', $response);

                if ($response->code >= 400 || $response->code < 0) {
                    break;
                }

            } catch (\Exception $e) {
                echo "[Error] worker pid: " . Worker::getProcessId() . ", e: " . $e->getMessage() . PHP_EOL;
                break;
            }
        }
        return $signal;
    }

    /**
     * get child process sign
     * @return int
     */
    private function getProcessSignal()
    {
        if (!Worker::checkProcess(Worker::getMasterPid())) {
            return 2;
        }

        if (Worker::getStatus() == false) {
            return 3;
        }

        return 0;
    }

    /**
     * restart child process
     *
     * @param array $info array process info
     * [
     *     'pid' => 1234,
     *     'code' => 0,
     *     'signal' => 15
     * ]
     * @throws \Exception
     */
    private function restart($info)
    {
        if (!isset($this->workers[$info['pid']])) {
            throw new \Exception('restart process Error: no pid');
        }

        $worker = $this->workers[$info['pid']];

        swoole_event_del($worker['process']->pipe);
        $worker['process']->close();

        unset($this->workers[$info['pid']]);

        $this->WorkerStart(intval($worker['id']));
    }

    /**
     * async listen SIGCHLD
     */
    private function asyncProcessWait()
    {
        \Swoole\Process::signal(SIGCHLD, function ($sig) {
            while ($ret = \Swoole\Process::wait(false)) {
                $this->restart($ret);
            }
        });
    }
}