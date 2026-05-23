<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(21);
if (! $user) {
    echo "User 21 not found\n";
    exit(1);
}

$token = $user->createToken('test')->plainTextToken;

$response = Illuminate\Support\Facades\Http::withToken($token)
    ->acceptJson()
    ->get('http://127.0.0.1:8000/api/v1/parent/dashboard');

echo 'HTTP ' . $response->status() . "\n";
echo $response->body() . "\n";
