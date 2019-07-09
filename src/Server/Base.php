<?php

namespace Swover\Server;

use Swover\Utils\Config;
use Swover\Utils\Event;
use Swover\Utils\Response;

abstract class Base
{
    protected static $instance = [];

    protected $booted = false;

    protected $server_type = '';

    protected $daemonize = false;

    protected $process_name = 'server';

    protected $worker_num = 1;

    protected $task_worker_num = 0;

    protected $max_request = 0;

    protected $entrance = '';

    /**
     * @var Event
     */
    protected $event = null;

    /**
     * @var Config
     */
    protected $config = null;

    private function __construct()
    {
        $this->config = Config::getInstance();

        $this->prepare();

        if (!$this->entrance) {
            throw new \Exception('Has Not Entrance!');
        }

        $this->event = Event::getInstance();

        $this->event->register($this->config->get('events', []));
    }

    /**
     * Get Server single instance
     * There only one instance in a process, and only be started once
     *
     * @return static
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (!isset(self::$instance[static::class]) || is_null(static::$instance[static::class])) {
            self::$instance[static::class] = new static();
        }
        return self::$instance[static::class];
    }

    public function boot()
    {
        if ($this->booted) {
            return false;
        }

        $this->booted = true;

        $this->start();
        return true;
    }

    abstract protected function start();

    abstract protected function execute($data = null);

    private function prepare()
    {
        foreach ($this->config as $key => $value) {

            if (!isset($this->$key)) continue;

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
}

