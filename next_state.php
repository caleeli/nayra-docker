<?php

use ProcessMaker\NayraService\BpmnAction;

require 'vendor/autoload.php';

///////////////////////////////////////////
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
///////////////////////////////////////////

$collaborationId = $_GET['id'];
$callback = $_GET['callback'];
$deploy_name = $_GET['deploy_name'];

$post = file_get_contents('php://input');
$newTransactions = json_decode($post, true);

// $instance = json_decode(file_get_contents($instances_path . '/' . $collaborationId . '.json'), true);
// load state from instances_path
$transactions = [];
foreach (glob('instances/' . $collaborationId . '.*.json') as $filename) {
    $transactions[] = json_decode(file_get_contents($filename), true);
}
$state = BpmnAction::transactionsToState($transactions);
error_log('next stateeee');
error_log('==> ' . json_encode($state));

$actionAttributes = [
    "bpmn" => $deploy_name,
    "action" => 'NEXT_STATE',
    "params" => [
        'transactions' => $newTransactions,
    ],
    'state' => $state,
    'callback' => $callback,
];

$action = new BpmnAction($actionAttributes);
$action->execute();
