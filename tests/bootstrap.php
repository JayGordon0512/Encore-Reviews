<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoloadPath = getenv('ENCORE_VENDOR_AUTOLOAD') ?: $projectRoot.'/vendor/autoload.php';

if (! is_file($autoloadPath)) {
    throw new RuntimeException(
        'Composer autoload file not found. Run composer install or set ENCORE_VENDOR_AUTOLOAD.',
    );
}

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require $autoloadPath;
$loader->addPsr4('App\\', $projectRoot.'/app/', true);
$loader->addPsr4('Database\\Factories\\', $projectRoot.'/database/factories/', true);
$loader->addPsr4('Database\\Seeders\\', $projectRoot.'/database/seeders/', true);
$loader->addPsr4('Tests\\', $projectRoot.'/tests/', true);

// Optimised Composer classmaps may point at the dependency-owning worktree.
// Redirect those project entries to matching files in this worktree.
$dependencyRoot = dirname($autoloadPath, 2);
$localClassMap = [];
foreach ($loader->getClassMap() as $class => $path) {
    $resolvedPath = realpath($path) ?: $path;
    foreach (['app', 'database/factories', 'database/seeders', 'tests'] as $directory) {
        $sourcePrefix = $dependencyRoot.'/'.$directory.'/';
        if (! str_starts_with($resolvedPath, $sourcePrefix)) {
            continue;
        }

        $localPath = $projectRoot.'/'.$directory.'/'.substr($resolvedPath, strlen($sourcePrefix));
        if (is_file($localPath)) {
            $localClassMap[$class] = $localPath;
        }
    }
}
$loader->addClassMap($localClassMap);

require_once $projectRoot.'/tests/CreatesApplication.php';
require_once $projectRoot.'/tests/TestCase.php';
