<?php

namespace Swover\Server;

use Swover\Utils\Worker;

/**
 * Process Server
 */
class Process extends Base
{
    //child-process index => pid
    private $works = [];

    //child-process index => process
    private $processes = [];

    public function __construct($table)
    {
        try {
            parent::__construct($table);

            if ($this->daemonize === true) {
                \swoole_process::daemon(true, false);
            }

            $this->_setProcessName('master');

            Worker::setMasterPid(posix_getpid());

            for ($i = 0; $i < $this->worker_num; $i++) {
                $this->CreateProcess($i);
            }

            $this->asyncProcessWait();

        } catch (\Exception $e) {
            die('Start error: ' . $e->getMessage());
        }
    }

    /**
     * create process
     */
    private function CreateProcess($index)
    {
        $process = new \swoole_process(function (\swoole_process $worker) use ($index) {

            $this->_setProcessName('worker_'.$index);

            Worker::setChildStatus(true);

            pcntl_signal(SIGUSR1, function ($signo) {
                Worker::setChildStatus(false);
            });

            $request_count = 0;
            $signal = 0;
            while (true) {
                $signal = $this->getProcessSignal($request_count);
                if ($signal > 0) {
                    break;
                }

                try {
                    $result = $this->entrance();
                    if ($result === false) {
                        break;
                    }
                } catch (\Exception $e) {
                    $this->log("[Error] worker id: {$worker->pid}, e: " . $e->getMessage());
                    break;
                }
            }
            $this->log("[#{$worker->pid}]\tWorker-{$index}: shutting down by {$signal}..");
            sleep(mt_rand(1,3));
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

        if (! Worker::checkMaster() ) {
            return 2;
        }

        if (Worker::getChildStatus() == false) {
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
            $new_pid = $this->CreateProcess($index);
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