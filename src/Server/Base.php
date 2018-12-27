<?php

namespace Swover\Server;


use Swover\Utils\Cache;

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

    public function __construct()
    {
        $this->initConfig();
    }

    private function initConfig()
    {
        $this->entrance = Cache::get('entrance');
        if (!$this->entrance) {
            die('Has Not Entrance!');
        }

        $this->server_type = Cache::get('server_type');

        $this->daemonize = boolval(Cache::get('daemonize', false));

        $this->process_name = Cache::get('process_name');

        $this->worker_num = Cache::get('worker_num', 1);

        $this->task_worker_num = Cache::get('task_worker_num', 1);

        $this->max_request = intval(Cache::get('max_request', 0));

        $this->log_file = Cache::get('log_file');
        if (!$this->log_file) {
            $this->log_file = '/tmp/' . $this->process_name . '/swoole.log';
            Cache::set('log_file', $this->log_file);
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
        if (empty($entrance)) {
            throw new \Exception('Has not entrance!');
        }

        $class = $entrance[0];
        $method = 'run';
        if (count($entrance) >= 2) {
            $method = $entrance[1];
        }

        return call_user_func_array([$class, $method], [$request]);
    }

    /**
     * write message to log_file
     */
    protected function log($msg)
    {
        error_log(date('Y-m-d H:i:s').' '.ltrim($msg).PHP_EOL, 3, $this->log_file);
    }
}

