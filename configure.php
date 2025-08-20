#!/usr/bin/env php
<?php

declare(strict_types=1);

use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

require __DIR__.'/vendor/autoload.php';

function deleteDirectoryRecursively(string $dir): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }

    rmdir($dir);
}

const STUBS_DIR = __DIR__.'/stubs';

warning('Let\'s start configuring your new package:');

$vendor = text(
    label: 'Vendor name',
    default: 'dllobell',
    required: true,
);

$package = text(
    label: 'Package name',
    default: basename(__DIR__),
    required: true,
);

$description = text(
    label: 'Package description',
    placeholder: 'An awesome package',
);

$vendorNamespace = Str::of($vendor)->studly()->toString();
$packageNamespace = Str::of($package)->studly()->toString();

$namespace = suggest(
    label: 'Namespace',
    options: [
        "{$vendorNamespace}\\{$packageNamespace}",
        $packageNamespace,
    ],
    required: true,
);

$title = Str::of($package)->headline()->toString();

// Replace placeholders in stub files
$replacementMap = [
    ':vendor' => $vendor,
    ':package' => $package,
    ':namespaceEscaped' => str_replace('\\', '\\\\', $namespace),
    ':namespace' => $namespace,
    ':title' => $title,
    ':description' => $description,
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(STUBS_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
);

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
    if ($file->isDir()) {
        continue;
    }

    $contents = file_get_contents($file->getPathname());

    if ($contents === false) {
        continue;
    }

    $updated = str_replace(array_keys($replacementMap), array_values($replacementMap), $contents);

    if ($updated !== $contents) {
        file_put_contents($file->getPathname(), $updated);
    }
}

// Copy stubs to the package directory
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(STUBS_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
    $relativePath = str_replace(STUBS_DIR.DIRECTORY_SEPARATOR, '', $file->getPathname());
    $targetPath = __DIR__.DIRECTORY_SEPARATOR.$relativePath;

    if ($file->isDir()) {
        mkdir($targetPath, 0755, true);
    } else {
        copy($file->getPathname(), str_replace('.stub', '', $targetPath));
    }
}

// Clean up
deleteDirectoryRecursively(STUBS_DIR);

deleteDirectoryRecursively(__DIR__.'/vendor');
unlink(__DIR__.'/composer.lock');

unlink(__FILE__);

info("Your package {$vendor}/{$package} is ready! To start developing:");

note('<fg=gray>➜</> <options=bold>cd '.basename(__DIR__).'</>');
note('<fg=gray>➜</> <options=bold>composer install</>');
