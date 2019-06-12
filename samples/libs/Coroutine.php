<?php

// 测试协程
class Coroutine
{
    //单进程最大协程数
    public static $max = 2;

    //记录当前进程协程数的通道
    public static $channel = null;

    public static function http(\Swover\Utils\Request $request)
    {
        self::goStart();

        $id = $request->post['data']['id'];
        $chan = new \chan(1);
        go(function () use ($chan, $id) {
            defer(function () {
                self::$channel->pop();
            });
            echo "coroutine {$id} started!" . PHP_EOL;
            self::sleep();
            echo "coroutine {$id} execute!" . PHP_EOL;
            self::sleep();
            echo "coroutine {$id} finished!" . PHP_EOL;
            $chan->push('finish' . $id);
        });

        return $chan->pop();
    }

    public static function goStart()
    {
        if (self::$channel === null) {
            self::$channel = new \Swoole\Coroutine\Channel(self::$max);
        }
        return self::$channel->push(1);
    }

    public static function sleep()
    {
        co::sleep(mt_rand(3, 5));
    }
}
