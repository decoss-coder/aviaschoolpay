<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$realTestCommands = __DIR__.'/console-real-test.php';
if (file_exists($realTestCommands)) {
    require_once $realTestCommands;
}
