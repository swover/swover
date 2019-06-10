<?php

class Entrance
{
    public static function process()
    {
        //pull data from message middleware server
        $data = ['action' => 'test_process', 'data' => ['id' => mt_rand(100, 200)]];

        $result = self::execute($data);

        echo 'master:[' . \Swover\Utils\Worker::getMasterPid() . '] current:[' . posix_getpid() . '-' . \Swover\Utils\Worker::getChildStatus() . ']'
            . $result . PHP_EOL;
    }

    //tcp server
    public static function tcp(\Swover\Utils\Contracts\Request $request)
    {
        $data = $request['input'];
        if (!$data) {
            return "Has Not Input Data!";
        }
        $data = json_decode($data, true);
        return self::execute($data);
    }

    // HTTP server
    // Here are some examples of some entries.
    // In environment, You may need to a single entry.
    public static function http(\Swover\Utils\Contracts\Request $request)
    {
        return self::httpGet($request);
    }

    public static function httpGet(\Swover\Utils\Contracts\Request $request)
    {
        $data = $request['get'];
        return self::execute($data);
    }

    public static function httpPost(\Swover\Utils\Contracts\Request $request)
    {
        $data = $request['post'];
        return self::execute($data);
    }

    public static function httpInput(\Swover\Utils\Contracts\Request $request)
    {
        $data = $request['input'];
        if (!$data) {
            return "Has Not Input Data!";
        }
        $data = json_decode($data, true);
        return self::execute($data);
    }

    // In this example, action is used as the route
    // In production environment, use your own solution
    public static function execute($request)
    {
        if (!$request) {
            return "Has Not Request Data!";
        }

        if (!isset($request['action'])) {
            return "Has Not Action!";
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
        return isset($routes[$action]) ? $routes[$action] : 'Welcome';
    }
}