<?php

use ProcessMaker\NayraService\BpmnAction;

require 'vendor/autoload.php';

set_error_handler(function ($errno, $errstr, $err_file, $err_line) {
    error_log($errstr);
    throw new ErrorException($errstr, 0, $errno, $err_file, $err_line);
}, ini_get('error_reporting'));

if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs');
}
$instances_path = __DIR__ . '/instances';
$log_filename = __DIR__ . '/logs/nayra.log';
$log_file = fopen($log_filename, 'a');
$_ENV['NAYRA_SERVER_URL'] = 'http://127.0.0.1/projects/NayraDocker';

$post = file_get_contents('php://input');
$post = json_decode($post, true);

$action = new BpmnAction($post);
$action->execute();

echo json_encode($action->request->engine->transactions);
