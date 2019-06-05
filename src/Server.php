<?php

namespace Swover;

use Swover\Server\Process;
use Swover\Server\Socket;

class Server
{
    private $config = [];

    /**
     * service type
     */
    private $server_type = [
        'tcp',
        'http',
        'process'
    ];

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!isset($this->config['server_type']) || !in_array($this->config['server_type'], $this->server_type)) {
            die('server_type defined error!' . PHP_EOL);
        }

        if (!isset($this->config['process_name'])) {
            die('process_name defined error!' . PHP_EOL);
        }
    }

    /**
     * start server
     */
    public function start()
    {
        if (!empty($this->getAllPid())) {
            echo 'Process names[' . $this->config['process_name'] . '] already exists, you have to wait 5 seconds for confirmation or it will start normally.' . PHP_EOL;
            for ($i = 1; $i <= 5; $i++) {
                sleep(1);
                echo $i . ' ';
            }
            echo PHP_EOL;
        }

        echo "Starting {$this->config['process_name']} ..." . PHP_EOL;

        try {
            if ($this->config['server_type'] == 'process') {
                new Process($this->config);
            } else {
                new Socket($this->config);
            }
        } catch (\Exception $e) {
            echo "{$this->config['process_name']} start fail. error: ".  $e->getMessage() . PHP_EOL;
            return false;
        }

        echo "{$this->config['process_name']} start success." . PHP_EOL;
        return true;
    }

    /**
     * safe stop server
     */
    public function stop()
    {
        $pid = $this->getPid('master');

        if (empty($pid)) {
            echo "{$this->config['process_name']} has not master process." . PHP_EOL;
            return true;
        }

        exec("kill -15 " . implode(' ', $pid), $output, $return);
        if ($return === false) {
            echo "{$this->config['process_name']} stop fail" . PHP_EOL;
            return false;
        }

        $stopped = false;
        for ($i = 0; $i < 10; $i++) {
            if (empty($this->getAllPid())) {
                $stopped = true;
                break;
            }
            sleep(mt_rand(1, 3));
        }

        if (!$stopped) {
            echo "{$this->config['process_name']} did not stop altogether." . PHP_EOL;
            return false;
        }

        echo "{$this->config['process_name']} stop success" . PHP_EOL;
        return true;
    }

    /**
     * safe restart server
     */
    public function restart()
    {
        if ($this->stop() != true) {
            echo "{$this->config['process_name']} has not stopped, restart fail." . PHP_EOL;
            return false;
        }

        return $this->start();
    }

    /**
     * safe reload worker process
     */
    public function reload()
    {
        if ($this->config['server_type'] == 'process') {
            $pid = $this->getPid('worker');
        } else {
            $pid = $this->getPid('master');
        }

        if (empty($pid)) {
            echo "{$this->config['process_name']} has not process" . PHP_EOL;
            return false;
        }

        exec("kill -USR1 " . implode(' ', $pid), $output, $return);

        if ($return === false) {
            echo "{$this->config['process_name']} reload fail" . PHP_EOL;
            return false;
        }
        echo "{$this->config['process_name']} reload success" . PHP_EOL;
        return true;
    }

    /**
     * force stop server
     */
    public function force()
    {
        $pids = $this->getAllPid();

        if (empty($pids)) {
            echo "{$this->config['process_name']} has not process" . PHP_EOL;
            return true;
        }

        exec("kill -9 " . implode(' ', $pids), $output, $return);
        if ($return === false) {
            echo "{$this->config['process_name']} stop fail" . PHP_EOL;
            return false;
        }

        $stopped = false;
        for ($i = 0; $i < 10; $i++) {
            if (empty($this->getAllPid())) {
                $stopped = true;
                break;
            }
            sleep(mt_rand(1, 3));
        }

        if (!$stopped) {
            echo "{$this->config['process_name']} did not stop altogether." . PHP_EOL;
            return false;
        }

        echo "{$this->config['process_name']} stop success" . PHP_EOL;
        return true;
    }

    /**
     * get all server process IDS
     */
    private function getAllPid()
    {
        $types = ['master', 'manager', 'worker'];
        $pids = [];
        foreach ($types as $type) {
            $pids = array_merge($pids, $this->getPid($type));
        }
        return $pids;
    }

    /**
     * get in-type process IDS
     */
    private function getPid($type = 'master')
    {
        $cmd = "ps aux | grep 'php {$this->config['process_name']} {$type}' | grep -v grep  | awk '{ print $2}'";
        exec($cmd, $pid);
        return $pid;
    }
}
