<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

define('LARAVEL_START', microtime(true));

// Verifica modo manutenção
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Carrega autoloader do Composer
require __DIR__.'/../vendor/autoload.php';

// Inicializa a aplicação Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';

// Captura e manipula a requisição
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);