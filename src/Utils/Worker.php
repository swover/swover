<?php
namespace Swover\Utils;

class Worker
{
    //master process id
    private static $master_pid = 0;

    //current process status
    private static $child_status = true;

    public static function setMasterPid($pid)
    {
        self::$master_pid = $pid;
    }

    public static function getMasterPid()
    {
        return self::$master_pid;
    }

    public static function getPid()
    {
        return posix_getpid();
    }

    /**
     * check master process still alive
     */
    public static function checkMaster()
    {
        return \swoole_process::kill(self::getMasterPid(), 0);
    }

    public static function setChildStatus($status)
    {
        self::$child_status = $status;
    }

    public static function getChildStatus()
    {
        pcntl_signal_dispatch();
        return self::$child_status;
    }
}
