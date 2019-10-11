<?php

class Entrance
{
    public static function process()
    {
        //pull data from message middleware server
        $data = ['action' => 'test_process', 'data' => ['id' => mt_rand(100, 200)]];

        $result = self::execute('', $data);

        echo 'worker : [' . \Swover\Worker::getProcessId() . ']'
            . $result . PHP_EOL;
    }

    //tcp server
    public static function tcp(\Swover\Contracts\Request $request)
    {
        return self::execute($request->path(), $request);
    }

    // HTTP server
    // Here are some examples of some entries.
    // In environment, You may need to a single entry.
    public static function http(\Swover\Contracts\Request $request)
    {
        return self::execute($request->path(), $request);
    }

    /**
     * In this example, action is used as the route
     * In production environment, use your own solution
     * @param $path
     * @param array | \Swover\Contracts\Request $request
     * @return bool|string
     */
    public static function execute($path, $request)
    {
        if ($request instanceof \Swover\Contracts\Request) {
            $input = json_decode($request->input(), true);
            $post = $request->post();
            $get = $request->get();
        } elseif (is_array($request)) {
            $input = $post = [];
            $get = $request;
        } else {
            echo 'Request error!';
            return false;
        }

        if (!$path || $path == '/') {
            if (isset($input['action']) && $input['action']) {
                $path = $input['action'];
            } elseif (isset($post['action']) && $post['action']) {
                $path = $post['action'];
            } elseif (isset($get['action']) && $get['action']) {
                $path = $get['action'];
            }
        }

        sleep(1);
        $route = self::route($path);
        return ' route: ' . $route
            . " get :" . json_encode($get, JSON_UNESCAPED_UNICODE)
            . " post :" . json_encode($post, JSON_UNESCAPED_UNICODE)
            . " input :" . json_encode($input, JSON_UNESCAPED_UNICODE);
    }

    private static function route($action)
    {
        $routes = [
            '/user/fav' => '\\Http\\Controller\\User::favourite',
            'test_process' => '\\A\\B\\C::test',
            'test_tcp' => '\Test\Tcp::run',
            'test_http' => '\Test\Http::run'
        ];
        return $routes[$action] ?? 'Welcome';
    }
}