<?php
namespace Swover\Utils;

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
    private static $process_pid = 0;

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

    public static function setPid($pid = 0)
    {
        if (!$pid) {
            $pid = posix_getpid();
        }
        self::$process_pid = $pid;
    }

    public static function getPid()
    {
        if (!self::$process_pid) {
            static::setPid();
        }
        return self::$process_pid;
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
        pcntl_signal_dispatch();
        return self::$status;
    }

    /**
     * Detect if a process exists
     * @param int $pid
     * @return mixed
     */
    public static function checkProcess($pid)
    {
        return \swoole_process::kill($pid, 0);
    }
}
