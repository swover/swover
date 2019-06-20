<?php

if (!isset($argv[1])) die;

include 'config/config.php';

call_user_func($argv[1]);

//normal HTTP client, GET & POST
function http()
{
    $config = getConfig('http');

    $url = "http://{$config['host']}:{$config['port']}/user/fav?action=reload_server";
    $post_data = ['action' => 'test_http', 'data' => ['id' => mt_rand(100, 200)]];
    echo 'post_data: ' . json_encode($post_data) . PHP_EOL;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($curl, CURLOPT_COOKIE , "id=9527;name=ruesin" );
    curl_setopt($curl, CURLOPT_HTTPHEADER, []);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    echo "Response Info: " . json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;
    echo "Result: " . $output . PHP_EOL;
}

//HTTP input client, php://input
function input()
{
    $config = getConfig('http');

    $url = "http://{$config['host']}:{$config['port']}";
    $post_data = ['action' => 'test_input', 'data' => ['id' => mt_rand(100, 200)]];
    echo 'post_data: ' . json_encode($post_data) . PHP_EOL;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_COOKIE , "id=9527;name=ruesin" );
    curl_setopt($curl, CURLOPT_HTTPHEADER, []);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    echo "Response Info: " . json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL . PHP_EOL;
    echo "Result: " . $output . PHP_EOL;
}



//tcp client
function tcp()
{
    $config = getConfig('tcp');

    $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
    $client->connect($config['host'], $config['port'], -1);

    $requst = ['action' => 'test_tcp', 'data' => ['id' => mt_rand(200, 300)]];
    echo json_encode($requst) . PHP_EOL;
    $client->send(json_encode($requst));
    echo $client->recv();
    $client->close();
}

function process($config)
{
}

