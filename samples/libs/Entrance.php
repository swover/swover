<?php

class Entrance
{
    public static function process()
    {
        //pull data from queue
        $data = ['action' => 'test_process', 'data' => [ 'id' => 123 ]];
        $request = \Swover\Utils\Request::getInstance($data);
        $result = self::execute($data);
        if(mt_rand(1,3) == 2) {
            throw new \Exception('mt_rand_error');
        }
        echo 'master:['.\Swover\Utils\Worker::getMasterPid().'] current:['.posix_getpid().'-'.\Swover\Utils\Worker::getChildStatus().']'
            .$result.PHP_EOL;
        // sleep(300);
        echo \Swover\Utils\Worker::getMasterPid().'finish';
    }

    public static function tcp()
    {
        $request = \Swover\Utils\Request::getInstance();
        return self::execute($request);
    }

    public static function http()
    {
        $request = \Swover\Utils\Request::getInstance();
        if (!$request->action) {
            return ['message'=>'action error'];
        }
        return self::execute($request);
    }

    private static function execute($request)
    {
        if (count($request) <= 0 && strlen($request) > 0) {
            echo "request is string: {$request}";
            $request = json_decode($request, true);
        }

        sleep(mt_rand(1,3));
        $route = self::route($request['action']);

        $body = " body :".json_encode($request, JSON_UNESCAPED_UNICODE).' route: '. $route;

        $response = \Swover\Utils\Response::getInstance();

        $response->body($body);

        $mt_rand = mt_rand(1,3);
        if ($mt_rand == 2) {
            echo $mt_rand.PHP_EOL;
            $response->status(302);
        }

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