<?php

namespace Swover\Server;

use Swover\Utils\Request;
use Swover\Utils\Response;

abstract class Base
{
    protected $server_type = '';

    protected $daemonize = false;

    protected $process_name = 'server';

    protected $worker_num = 1;

    protected $task_worker_num = 1;

    protected $max_request = 0;

    protected $entrance = '';

    protected $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->initConfig();

        if (!$this->entrance) {
            throw new \Exception('Has Not Entrance!');
        }

        $this->boot();
    }

    abstract protected function boot();

    abstract protected function execute($data = null);

    private function initConfig()
    {
        if (!isset($this->config['setting'])) {
            $this->config['setting'] = [];
        }

        foreach ($this->config['setting'] as $key => $item) {
            if (isset($this->config[$key])) {
                $this->config[$key] = $item;
            }
        }
        
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
     *
     * @param null $request
     * @return mixed|Response
     */
    protected function entrance($request = null)
    {
        $request = new Request($request);

        $result = call_user_func_array($this->entrance, [$request]);

        if ($result instanceof Response) {
            $response = $result;
        } else {
            $response = new Response();
        }

        if (is_string($result) || is_numeric($result)) {
            $response->body($result);
        }

        if (is_bool($result) && $result === false) {
            $response->status(500);
        }

        return $response;
    }

    /**
     * write message to log_file
     */
    protected function log($msg)
    {
        if ($this->log_file != '') {
            error_log(date('Y-m-d H:i:s') . ' ' . ltrim($msg) . PHP_EOL, 3, $this->log_file);
        } else {
            echo trim($msg) . PHP_EOL;
        }
    }

    public function __get($name)
    {
        if (isset($this->config['setting'][$name])) {
            return $this->config['setting'][$name];
        }

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

