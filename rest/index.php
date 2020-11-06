<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$server = new \Pressmind\REST\Server(str_replace(BASE_PATH, '', __DIR__));
$server->handle();
