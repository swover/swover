<?php

namespace Swover\Server;

use Swover\Worker;

/**
 * Process Server
 */
class Process extends Base
{
    //child-process index => pid
    private $works = [];

    //child-process index => process
    private $processes = [];

    protected function boot()
    {
        if (!extension_loaded('pcntl')) {
            throw new \Exception('Process required pcntl-extension!');
        }

        $this->start();
    }

    private function start()
    {
        if ($this->daemonize === true) {
            \swoole_process::daemon(true, false);
        }

        $this->_setProcessName('master');

        Worker::setMasterPid(posix_getpid());

        for ($i = 0; $i < $this->worker_num; $i++) {
            $this->createProcess($i);
        }

        $this->asyncProcessWait();
    }

    /**
     * create process
     */
    private function createProcess($index)
    {
        $process = new \swoole_process(function (\swoole_process $worker) use ($index) {

            $this->_setProcessName('worker_'.$index);

            Worker::setStatus(true);

            pcntl_signal(SIGUSR1, function ($signo) {
                Worker::setStatus(false);
            });

            $signal = $this->execute();

            $this->log("[#{$worker->pid}]\tWorker-{$index}: shutting down by {$signal}..");
            $worker->exit();
        }, $this->daemonize ? true : false);

        $pid = $process->start();

        \swoole_event_add($process->pipe, function ($pipe) use ($process) {
            $data = $process->read();
            if ($data) {
                $this->log($data);
            }
        });

        $this->processes[$index] = $process;
        $this->works[$index] = $pid;
        return $pid;
    }

    protected function execute($data = null)
    {
        $request_count = 0;
        $signal = 0;
        while (true) {
            $signal = $this->getProcessSignal($request_count);
            if ($signal > 0) {
                break;
            }

            try {
                $response = $this->entrance();

                if ($response->code >= 400 || $response->code < 0) {
                    break;
                }

            } catch (\Exception $e) {
                $this->log("[Error] worker pid: ".Worker::getProcessId().", e: " . $e->getMessage());
                break;
            }
        }
        return $signal;
    }

    /**
     * get child process sign
     * @return int
     */
    private function getProcessSignal(&$request_count)
    {
        if ($this->max_request > 0) {
            if ($request_count > $this->max_request) {
                return 1;
            }
            $request_count ++;
        }

        if (! Worker::checkProcess(Worker::getMasterPid()) ) {
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
     * @param $ret array process info
     * @throws \Exception
     */
    private function restart($ret)
    {
        $pid = $ret['pid'];
        $index = array_search($pid, $this->works);
        if ($index !== false) {

            \swoole_event_del($this->processes[$index]->pipe);
            $this->processes[$index]->close();

            $index = intval($index);
            $new_pid = $this->createProcess($index);
            $this->log("[#{$new_pid}]\tWorker-{$index}: restarted..");
            return;
        }
        throw new \Exception('restart process Error: no pid');
    }

    /**
     * async listen SIGCHLD
     */
    private function asyncProcessWait()
    {
        \swoole_process::signal(SIGCHLD, function ($sig) {
            while ($ret = \swoole_process::wait(false)) {
                $this->restart($ret);
            }
        });
    }
}