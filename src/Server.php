<?php

namespace Swover;

use Swover\Server\Http;
use Swover\Server\Process;
use Swover\Server\Tcp;
use Swover\Server\WebServer;
use Swover\Utils\Config;

class Server
{
    /**
     * @var Config
     */
    private $config;

    const SERVER_TYPES = [
        'socket',
        'tcp',
        'http',
        'websocket',
        'udp',
        'process'
    ];

    public function __construct(array $config)
    {
        $this->config = Config::getInstance($config);

        if (!isset($this->config['server_type'])
            || !in_array($this->config['server_type'], self::SERVER_TYPES)) {
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
            $this->log('Process names[' . $this->config['process_name'] . '] already exists, you have to wait 5 seconds for confirmation or it will start normally.');
            for ($i = 1; $i <= 5; $i++) {
                sleep(1);
                $this->log($i . ' s.');
            }
        }

        $this->log("Starting {$this->config['process_name']} ...");

        try {
            switch ($this->config['server_type']) {
                case 'process':
                    $server = Process::getInstance();
                    break;
                case 'tcp':
                case 'socket':
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
            }
            $server->boot();
        } catch (\Exception $e) {
            return $this->failure("{$this->config['process_name']} start fail. error: " . $e->getMessage() . ' ' . $e->getTraceAsString());
        }

        return $this->success("{$this->config['process_name']} start success.");
    }

    /**
     * Forced to stop server
     */
    public function force()
    {
        $this->stop(true);
    }

    /**
     * safe stop server
     * @param bool $force Forced to stop server
     * @return bool
     */
    public function stop($force = false)
    {
        if ($force) {
            $pid = $this->getAllPid();
        } else {
            $pid = $this->getPid('master');
        }

        if (empty($pid)) {
            return $this->success("{$this->config['process_name']} has not process.");
        }

        if ($force) {
            exec("kill -9 " . implode(' ', $pid), $output, $return);
        } else {
            exec("kill -15 " . implode(' ', $pid), $output, $return);
        }

        if ($return === false) {
            return $this->failure("{$this->config['process_name']} stop fail");
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
            return $this->failure("{$this->config['process_name']} did not stop altogether.");
        }

        return $this->success("{$this->config['process_name']} stop success");
    }

    /**
     * safe restart server
     */
    public function restart()
    {
        if ($this->stop() != true) {
            $this->log('Restart fail, do you want to force restart ' . $this->config['process_name'] . '?');
            $this->log('You have to wait 5 seconds for confirmation or it will force restart.');
            for ($i = 1; $i <= 5; $i++) {
                sleep(1);
                $this->log($i . ' s.');
            }
            if ($this->force() != true) {
                return $this->failure('[' . $this->config['process_name'] . '] restart fail !!!');
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
            return $this->failure("{$this->config['process_name']} has not process");
        }

        exec("kill -USR1 " . implode(' ', $pid), $output, $return);

        if ($return === false) {
            return $this->failure("{$this->config['process_name']} reload fail");
        }
        return $this->success("{$this->config['process_name']} reload success");
    }

    /**
     * get all server process IDS
     */
    private function getAllPid()
    {
        $types = ['master', 'manager', 'worker'];
        $pid = [];
        foreach ($types as $type) {
            $pid = array_merge($pid, $this->getPid($type));
        }
        return $pid;
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

    private function log($message)
    {
        echo $message . PHP_EOL;
    }

    private function success($message)
    {
        $this->log($message);
        return true;
    }

    private function failure($message)
    {
        $this->log($message);
        return false;
    }
}
