<?php

namespace Swover\Server;

use Swover\Utils\Event;
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

        Event::getInstance()->register($this->getConfig('events', []));

        $this->boot();
    }

    abstract protected function boot();

    abstract protected function execute($data = null);

    private function initConfig()
    {
        foreach ($this->getConfig('setting', []) as $key => $item) {
            if ($key == 'setting') continue;
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
     * @param \Swover\Contracts\Request $request
     * @return mixed | \Swover\Contracts\Response
     */
    protected function entrance($request)
    {
        $result = call_user_func_array($this->entrance, [$request]);

        if ($result instanceof \Swover\Contracts\Response) {
            $response = $result;
        } else {
            $response = new Response();
        }

        if (is_string($result) || is_numeric($result)) {
            $response->setBody($result);
        }

        if (is_bool($result) && $result === false) {
            $response->setCode(500);
        }

        return $response;
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

    public function getConfig($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : (isset($this->config['setting'][$name]) ? $this->config['setting'][$name] : $default);
    }
}

