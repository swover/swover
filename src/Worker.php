<?php
namespace Swover;

class Worker
{
    /**
     * Master process ID
     * @var int
     */
    private static $master_pid = 0;

    /**
     * Current process ID
     * @var int
     */
    private static $process_id = 0;

    /**
     * Current process status
     *
     * False means to exit the current process
     * @var bool
     */
    private static $status = true;

    public static function setMasterPid($pid)
    {
        self::$master_pid = $pid;
    }

    public static function getMasterPid()
    {
        return self::$master_pid;
    }

    public static function setProcessId($pid = 0)
    {
        if (!$pid) {
            $pid = posix_getpid();
        }
        self::$process_id = $pid;
    }

    public static function getProcessId()
    {
        if (!self::$process_id) {
            static::setProcessId();
        }
        return self::$process_id;
    }

    public static function setStatus($status)
    {
        self::$status = $status;
    }

    /**
     * The state is set when the current process receives a Linux signal
     * @return bool
     */
    public static function getStatus()
    {
        function_exists('pcntl_signal_dispatch') && pcntl_signal_dispatch();
        return self::$status;
    }

    /**
     * Detect if a process exists
     * @param int $pid
     * @return mixed
     */
    public static function checkProcess($pid)
    {
        return \Swoole\Process::kill($pid, 0);
    }
}
