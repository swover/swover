<?php

class Entrance
{
    public static function process()
    {
        $data = ['action' => 'test_process', 'data' => ['id' => mt_rand(100, 200)]];

        $result = self::execute($data);

        echo 'master:[' . \Swover\Utils\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Utils\Worker::getChildStatus() . ']'
            . $result . PHP_EOL;
    }

    public static function tcp($request)
    {
        return self::execute($request);
    }

    public static function http($request)
    {
        if (!$request->action) {
            return ['message'=>'action error'];
        }
        return self::execute($request);
    }

    public static function execute($request)
    {
        //判断是否为string，比如通过TCP通信的Json格式的消息体
        if (count($request) <= 0 && strlen($request) > 0) {
            echo "request is string: {$request}";
            $request = json_decode($request, true);
        }
        sleep(1);
        $route = self::route($request['action']);

        return " data :".json_encode($request, JSON_UNESCAPED_UNICODE).' route: '. $route;
    }

    private static function route($action)
    {
        $routes = [
            'test_process' => '\\A\\B\\C::test',
            'test_tcp'     => '\Test\Tcp::run',
            'test_http'    => '\Test\Http::run'
        ];
        return $routes[$action];
    }
}