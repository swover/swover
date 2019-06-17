<?php

namespace Swover\Tests;

use PHPUnit\Framework\TestCase;

class Worker extends TestCase
{
    public function testMaster()
    {
        $master_id = 100;
        $this->assertEquals(0, \Swover\Worker::getMasterPid());
        \Swover\Worker::setMasterPid($master_id);
        $this->assertEquals($master_id, \Swover\Worker::getMasterPid());
    }

    public function testProcess()
    {
        $process_id = 100;
        $this->assertEquals(posix_getpid(), \Swover\Worker::getProcessId());
        \Swover\Worker::setProcessId($process_id);
        $this->assertEquals($process_id, \Swover\Worker::getProcessId());
    }

    public function testStatus()
    {
        $this->assertEquals(true, \Swover\Worker::getStatus());
        \Swover\Worker::setStatus(false);
        $this->assertEquals(false, \Swover\Worker::getStatus());
    }

    public function testCheckProcess()
    {
        $this->assertEquals(true, \Swover\Worker::checkProcess(posix_getpid()));
        $this->assertEquals(false, \Swover\Worker::checkProcess(999999999999));
    }
}