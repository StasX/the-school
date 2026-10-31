<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if (
    ! $app->environment('testing') ||
    config('database.default') !== 'mysql' ||
    config('database.connections.mysql.database') !== 'school_test' ||
    config('database.connections.mysql.host') !== 'mariadb'
) {
    throw new RuntimeException('Refusing to prepare a database outside the isolated test stack.');
}

// Keep the production schema, but let each test create its own fixtures.
DB::transaction(function (): void {
    foreach (['school', 'students', 'courses', 'administrators'] as $table) {
        DB::table($table)->delete();
    }
});
