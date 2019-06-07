<?php

if (!isset($argv[1])) die('params error!');

include 'config/config.php';

//process tcp http
$server_type = $argv[1];

//start stop reload restart
$operate = isset($argv[2]) ? $argv[2] : 'start';

$class = new \Swover\Server(getConfig($server_type));

$class->$operate();

