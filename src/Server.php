<?php

namespace Swover;

use Swover\Server\Http;
use Swover\Server\Process;
use Swover\Server\Socket;
use Swover\Server\Tcp;
use Swover\Server\WebServer;
use Swover\Utils\Config;

class Server
{
    /**
     * @var Config
     */
    private $config = [];

    public function __construct(array $config)
    {
        $this->config = Config::getInstance($config);

        if (!isset($this->config['server_type'])
            || !in_array($this->config['server_type'], ['tcp', 'http', 'websocket' , 'process'])) {
            throw new \Exception('server_type defined error!' . PHP_EOL);
        }

        $this->config['process_name'] = $this->config->get('process_name', 'swover_' . $this->config['server_type']);
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
            switch ($this->config['server_type']) {
                case 'process':
                    $server = Process::getInstance();
                    break;
                case 'tcp':
                    $server = Tcp::getInstance();
                    break;
                case 'http':
                    $server = Http::getInstance();
                    break;
                case 'websocket':
                    $server = WebServer::getInstance();
                    break;
                default:
                    throw new \Exception("Get server instance failed!");
                    break;
            }
            $server->boot();
        } catch (\Exception $e) {
            echo "{$this->config['process_name']} start fail. error: " . $e->getMessage() . PHP_EOL;
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
            echo 'Restart fail, do you want to force restart ' . $this->config['process_name'] . '?' . PHP_EOL;
            echo 'You have to wait 5 seconds for confirmation or it will force restart.' . PHP_EOL;
            for ($i = 1; $i <= 5; $i++) {
                sleep(1);
                echo $i . ' ';
            }
            echo PHP_EOL;
            if ($this->force() != true) {
                echo '[' . $this->config['process_name'] . '] restart fail !!!' . PHP_EOL;
                return false;
            }
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
        $pid = [];
        //$cmd = "if [ `command -v ps >/dev/null 2>&1` ]; then ps aux | grep 'php {$this->config['process_name']} {$type}' | grep -v grep  | awk '{ print $2}'; fi";
        $cmd = "command -v ps >/dev/null 2>&1 || { echo 'no-ps-command'; exit 1;} && { ps aux | grep 'php {$this->config['process_name']} {$type}' | grep -v grep  | awk '{ print $2}'; }";
        if (function_exists('exec')) {
            exec($cmd, $pid, $result);
            if ($result != 0) {
                $pid = $this->getProcPid($type);
            }
        } elseif (function_exists('shell_exec')) {
            $result = shell_exec($cmd);
            $pid = array_filter(explode("\n", $result));
            if (isset($pid[0]) && $pid[0] == 'no-ps-command') {
                $pid = $this->getProcPid($type);
            }
        }
        return $pid;
    }

    private function getProcPid($type = 'master')
    {
        $pid = [];
        $path = '/proc/';
        if (!is_dir($path)) return $pid;
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                if (is_file($path . $file)) continue;
                if (intval($file) > 0 && strlen(intval($file)) == strlen($file)) {
                    if (!file_exists($path . $file . '/cmdline')) continue;
                    if (strpos(file_get_contents($path . $file . '/cmdline'), "php {$this->config['process_name']} {$type}") === false) continue;
                    $pid[] = $file;
                }
            }
        }
        return $pid;
    }
}
