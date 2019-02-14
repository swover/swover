<?php

if (!isset($argv[1])) die;

$config = include 'config/config.php';

//tcp http
$server_type = $argv[1];

$config = $config[$server_type];

call_user_func($server_type, $config);

function http($config)
{
    $url =  "http://{$config['host']}:{$config['port']}?action=reload_server";
    $post_data = ['action'=>'test_http','data'=> ['id'=>123] ];
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($curl,CURLOPT_HTTPHEADER, []);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);

    var_dump($output);
    echo PHP_EOL;
}

function tcp($config)
{
    $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
    $client->connect($config['host'], $config['port'], -1);

    $requst = ['action' => 'test_tcp', 'data'=>['id'=>234]];
    $time = time();
    $requst['sign'] = Sign::build($requst, $time);
    $requst['time'] = $time;

    $client->send(json_encode($requst));
    echo $client->recv();
    $client->close();
}

function process($config)
{

}

