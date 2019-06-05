<?php

namespace Swover\Server;

use Swover\Utils\Config;
use Swover\Utils\Request;

class Base
{
    protected $server_type = '';

    protected $daemonize = false;

    protected $process_name = 'server';

    protected $worker_num = 1;

    protected $task_worker_num = 1;

    protected $max_request = 0;

    protected $log_file = '';

    protected $entrance = '';

    protected $config = [];

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->initConfig();

        if (!$this->entrance) {
            die('Has Not Entrance!' . PHP_EOL);
        }
    }

    private function initConfig()
    {
        foreach ($this->config as $key => $value) {
            if ($key == 'daemonize') {
                $value = boolval($value);
            }
            if ($key == 'max_request') {
                $value = intval($value);
            }
            $this->$key = $value;
        }

        if ($this->worker_num <= 0) {
            $this->worker_num = 1;
        }

        if ($this->task_worker_num <= 0) {
            $this->task_worker_num = 1;
        }

        if (!$this->log_file) {
            $this->log_file = '/tmp/' . $this->process_name . '/swoole.log';
        }
        $log_path = dirname($this->log_file);
        if (!file_exists($this->log_file) || !file_exists($log_path)) {
            !is_dir($log_path) && mkdir($log_path, 0777, true);
        }
    }

    protected function _setProcessName($name)
    {
        $name = 'php ' . $this->process_name . ' ' . $name;
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($name);
        } elseif (function_exists('swoole_set_process_name')) {
            @swoole_set_process_name($name);
        } else {
            trigger_error(__METHOD__ . ' failed. require cli_set_process_title or swoole_set_process_name.');
        }
    }

    /**
     * Execute Application code
     */
    protected function entrance($request = null)
    {
        $entrance = explode('::', $this->entrance);
        $instance = $entrance[0];
        $method = isset($entrance[1]) ? $entrance[1] : 'run';

        Request::getInstance($request);

        $result = call_user_func_array([$instance, $method], []);

        Request::setInstance(null);

        if (is_string($result) || is_numeric($result) || is_bool($result)) {
            return $result;
        }
        if (is_array($result)) {
            return json_encode($result);
        }
        if (is_null($result)) {
            return 'null';
        }

        return 'none';
    }

    /**
     * write message to log_file
     */
    protected function log($msg)
    {
        error_log(date('Y-m-d H:i:s') . ' ' . ltrim($msg) . PHP_EOL, 3, $this->log_file);
    }

    public function __get($name)
    {
        if (!isset($this->config[$name])) {
            return false;
        }
        return $this->config[$name];
    }

    public function __set($name, $value)
    {
        return $this->config[$name] = $value;
    }
}

