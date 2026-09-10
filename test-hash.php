<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'guru.bk@ruangbk.test')->first();
echo 'Password: ' . $u->password . PHP_EOL;
var_dump(Hash::check('12345678', $u->password));
