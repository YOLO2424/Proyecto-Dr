<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\MigrationRunner;

$applied = MigrationRunner::run(true);
echo "Migraciones aplicadas: " . count($applied) . PHP_EOL;