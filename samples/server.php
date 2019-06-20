<?php

if (!isset($argv[1])) die('params error!');

include 'config/config.php';

//process tcp http
$config = getConfig($argv[1]);
$server_type = $config['server_type'];

//start stop reload restart
$operate = isset($argv[2]) ? $argv[2] : 'start';

$class = new \Swover\Server($config);

$class->$operate();

