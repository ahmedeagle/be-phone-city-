<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "OTO Pickup Config:\n";
print_r(config('services.oto.pickup'));

