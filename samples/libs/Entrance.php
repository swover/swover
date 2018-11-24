<?php

class Entrance
{
    public static function process()
    {
        //pull data from queue
        $data = ['action' => 'test_process', 'data' => [ 'id' => 123 ]];
        $result = self::execute($data);
        echo $result.PHP_EOL;
    }

    public static function tcp($request)
    {
        return self::execute($request);
    }

    public static function http($request)
    {
        return self::execute($request);
    }

    private static function execute($request)
    {
        sleep(mt_rand(1,3));
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