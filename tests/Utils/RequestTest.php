<?php

namespace Swover\Tests\Utils;

use Swover\Utils\Request;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testArrayGet()
    {
        $input = [
            'get' => [
                'gka' => 'gva',
                'gkb' => 'gvb'
            ],
            'header' => [],
            'server' => [],
        ];
        $instance = new Request($input);
        $this->assertEquals('gva', $instance->get('gka'));
        //$this->assertEquals($input['get'], $instance->get());
        $this->assertEquals(null, $instance->get('gkc'));
        $this->assertEquals('sin', $instance->get('name', 'sin'));
        $this->assertEquals('GET', $instance->method());
    }

    public function testArrayPost()
    {
        $input = [
            'post' => [
                'pka' => 'pva',
                'pkb' => 'pvb'
            ],
            'server' => [
                'request_method' => 'post'
            ]
        ];
        $instance = new Request($input);
        $this->assertEquals('pva', $instance->post('pka'));
        $this->assertEquals(null, $instance->post('pkc'));
        $this->assertEquals('Psin', $instance->post('name', 'Psin'));
        $this->assertEquals('POST', $instance->method());
    }

    public function testArrayInput()
    {
        $input = [
            'input' => json_encode(['ika' => 'iva', 'ikc' => 'ivc']),
            'header' => [],
            'server' => [
                'request_method' => 'post'
            ],
        ];
        $instance = new Request($input);
        $this->assertEquals($input['input'], $instance->input());
        $this->assertEquals('POST', $instance->method());
    }

    //TODO  header url method server

    public function testGet()
    {
        $request = new \Swoole\Http\Request();
        $request->get = [
            'gka' => 'gva',
            'gkb' => 'gvb'
        ];
        $request->server = [
            'request_method' => 'get',
            'query_string' => '?gka=gva&gkb=gvb'
        ];
        $instance = new Request($request);
        $this->assertEquals('gva', $instance->get('gka'));
        $this->assertEquals(null, $instance->get('gkc'));
        $this->assertEquals('sin', $instance->get('name', 'sin'));
        $this->assertEquals('GET', $instance->method());
        //$this->assertEquals('gka=gva&gkb=gvb', '');
    }

    public function testPost()
    {
        // $data = [
        //     'input' => $data,
        //     'server' => [
        //         'query_string' => '',
        //         'request_method' => 'GET',
        //         'request_uri' => '/',
        //         'path_info' => '/',
        //         'request_time' => $info['connect_time'],
        //         'request_time_float' => $info['connect_time'] . '.000',
        //         'server_port' => $info['server_port'],
        //         'remote_port' => $info['remote_port'],
        //         'remote_addr' => $info['remote_ip'],
        //         'master_time' => $info["last_time"],
        //         //'server_protocol' => 'HTTP/1.1',
        //         'server_software' => 'swoole-server'
        //     ]
        // ];

        $request = new \Swoole\Http\Request();
        $request->get = [
            'pka' => 'pva',
            'pkb' => 'pvb'
        ];

        $request->server = [
            'request_method' => 'post',
        ];

        $instance = new Request($request);
        $this->assertEquals('pva', $instance->get('pka'));
        $this->assertEquals(null, $instance->get('pkc'));
        $this->assertEquals('Psin', $instance->get('name', 'Psin'));
        $this->assertEquals('POST', $instance->method());
    }

    public function testInput()
    {
        $request = new \Swoole\Http\Request();
        $request->post = [json_encode(['ika' => 'iva', 'ikc' => 'ivc']) => null];
        $request->server = [
            'request_method' => 'post',
        ];

        $instance = new Request($request);
        // $this->assertEquals(json_encode(['ika' => 'iva', 'ikc' => 'ivc']), $instance->input());
        $this->assertEquals(null, $instance->input());
    }
}

