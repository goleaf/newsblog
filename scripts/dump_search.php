<?php

// Quick script to bootstrap the app and dump the HTML for /search with query params
// Usage: php scripts/dump_search.php "q=Post&category=1"

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

// Parse query string from argv
$queryString = $argv[1] ?? '';
parse_str($queryString, $params);

$request = Request::create('/search', 'GET', $params);
$response = $kernel->handle($request);

echo $response->getStatusCode(), "\n";
echo $response->getContent();

$kernel->terminate($request, $response);
