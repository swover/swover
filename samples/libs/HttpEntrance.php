<?php

class HttpEntrance extends \Swover\Utils\Entrance
{
    public function http()
    {
        if (!isset($this->action)) {
            return ['message'=>'has not action'];
        }

        if (!$this->action) {
            return 'action error!';
        }

        sleep(mt_rand(1,3));
        $route = self::route($this->action);

        return 'request: '.json_encode($this->request, JSON_UNESCAPED_UNICODE).' route: '. $route;
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