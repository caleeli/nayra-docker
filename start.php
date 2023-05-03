<?php

use Dotenv\Dotenv;
use Mark\App;
use ProcessMaker\NayraService\BpmnAction;
use ProcessMaker\NayraService\Monitor;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

require 'vendor/autoload.php';

// load .env file if exists
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Get processmaker/nayra version
$composer = json_decode(file_get_contents(__DIR__ . '/composer.lock'), true);
foreach ($composer['packages'] as $package) {
    if ($package['name'] == 'processmaker/nayra') {
        $version = $package['version'];
        break;
    }
}
$total_length = 93;
$line = '<n>' . \str_pad('<g> NAYRA SERVICE </g>', $total_length + \strlen('<g></g>'), '-', \STR_PAD_BOTH) . '</n>'. \PHP_EOL;
Worker::safeEcho($line);
$line = 'Nayra version: ' . $version . \PHP_EOL;
Worker::safeEcho($line);

if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs');
}
$log_filename = __DIR__ . '/logs/nayra.log';
$log_file = fopen($log_filename, 'a');
// Start workers
$api = new App('http://0.0.0.0:3000');

$api->count = 4; // process count

const base_headers = [
    'Content-Type' => 'application/json',
    'Access-Control-Allow-Origin'      => '*',
    'Access-Control-Allow-Credentials' => 'true',
    'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE',
    'Access-Control-Allow-Headers' => 'Content-Type,Authorization,X-Requested-With',
    'Server' => 'Phantom',
];

$api->any('/', function ($request) {
    ob_start();
    include 'welcome.php';
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
});

$api->post('/deploy', function (Request $request) {
    $file = $request->file('file');
    $name = $file['name'];
    copy($file['tmp_name'], 'bpmn/' . $name);
    return new Response(200, base_headers, json_encode([
        'message' => 'File uploaded successfully',
        'file' => $name,
    ]));
});

$api->get('/monitor', function (Request $request) {
    return new Response(200, base_headers, json_encode(Monitor::metrics(explode(',', $request->get('metrics')))));
});

$api->post('/actions', function (Request $request) {
    try {
        $action = new BpmnAction($request->post());
        $action->execute();
    } catch (Throwable $th) {
        $at = $th->getFile() . ':' . $th->getLine();
        error_log($th->getMessage() . ' at ' . $at);
    }
    return new Response(200, base_headers, json_encode($action->request->engine->transactions));
});

$api->start();
