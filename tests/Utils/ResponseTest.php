<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Utils\Response;

class ResponseTest extends TestCase
{
    public function testBody()
    {
        $body = "success";
        $instance = new Response();
        $this->assertEquals('', $instance['body']);
        $instance->setBody($body);
        $this->assertEquals($body, $instance['body']);
    }

    public function testCode()
    {
        $code = 500;
        $instance = new Response();
        $this->assertEquals(200, $instance['code']);
        $instance->setCode($code);
        $this->assertEquals($code, $instance['code']);
    }

    public function testHeader()
    {
        $headers = [
            'Connection' => 'close',
            'Content-Type' => 'text/html; charset=UTF-8',
            'Date' => 'Mon, 17 Jun 2019 11:05:19 GMT',
        ];
        $instance = new Response();
        $this->assertEquals([], $instance['header']);
        foreach ($headers as $key=>$value) {
            $instance->setHeader($key, $value);
        }
        $this->assertEquals($headers, $instance['header']);
    }

    public function testCookie()
    {
        $instance = new Response();

        $instance->setCookie('name', 'ruesin');
        $this->assertEquals('ruesin', $instance['cookie']['name']['value']);
        $this->assertEquals('', $instance['cookie']['name']['path']);

        $instance->setCookie('company', 'swover', 0, '/', 'github.com');
        $this->assertEquals('swover', $instance['cookie']['company']['value']);
        $this->assertEquals('github.com', $instance['cookie']['company']['domain']);
    }
}