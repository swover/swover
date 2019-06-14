<?php

/**
 * 测试强制停止服务
 * @throws Exception
 */
function forceKillProcess()
{
    $data = ['action' => 'test_force_process', 'data' => ['id' => mt_rand(100, 200)]];
    $result = Entrance::execute($data);
    echo 'master:[' . \Swover\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Worker::getStatus() . ']'
        . $result . PHP_EOL;
    sleep(300);
    echo \Swover\Worker::getMasterPid() . 'finish';
    return true;
}

/**
 * 随机报错
 */
function exceptionProcess()
{
    $data = ['action' => 'test_exception_process', 'data' => ['id' => mt_rand(100, 200)]];
    $result = Entrance::execute($data);
    if (mt_rand(1, 3) == 2) {
        throw new \Exception('mt_rand_error');
    }
    echo 'master:[' . \Swover\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Worker::getStatus() . ']'
        . $result . PHP_EOL;
}

