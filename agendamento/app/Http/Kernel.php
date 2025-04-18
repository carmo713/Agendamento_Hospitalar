<?php
namespace App\Http;

class Kernel
{
    protected $routeMiddleware = [
        // Outros middlewares...
        'role' => \App\Http\Middleware\CheckRole::class,
    ];
}