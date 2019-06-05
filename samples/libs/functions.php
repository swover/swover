<?php

/**
 * 测试强制停止服务
 * @throws Exception
 */
function forceKillProcess()
{
    $data = ['action' => 'test_force_process', 'data' => ['id' => mt_rand(100, 200)]];

    $result = Entrance::execute($data);

    echo 'master:[' . \Swover\Utils\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Utils\Worker::getChildStatus() . ']'
        . $result . PHP_EOL;
    sleep(300);
    echo \Swover\Utils\Worker::getMasterPid() . 'finish';
    return true;
}

/**
 * 随机报错
 */
function exceptionProcess()
{
    $data = ['action' => 'test_process', 'data' => ['id' => mt_rand(100, 200)]];
    $result = Entrance::execute($data);
    if (mt_rand(1, 3) == 2) {
        throw new \Exception('mt_rand_error');
    }
    echo 'master:[' . \Swover\Utils\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Utils\Worker::getChildStatus() . ']'
        . $result . PHP_EOL;
}

/**
 * 使用Request、Response单例
 */
function singleProcess()
{
    $data = ['action' => 'test_process', 'data' => ['id' => mt_rand(100, 200)]];

    $result = Entrance::execute($data);

    echo 'master:[' . \Swover\Utils\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Utils\Worker::getChildStatus() . ']'
        . $result . PHP_EOL;

    return true;
}

function normalTcp($request)
{
    return Entrance::execute($request);
}

function notmalHttp($request)
{
    if (!$request->action) {
        return ['message' => 'action error'];
    }
    return Entrance::execute($request);
}

