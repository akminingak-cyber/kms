#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * Module boundary checker.
 *
 * Enforces the rule that makes ADR-0001 valid:
 *
 *   A module may import from another module ONLY through that module's
 *   Contracts/ namespace. Nothing else — not an Eloquent model, not a helper,
 *   not a constant.
 *
 * Without mechanical enforcement the modular monolith degrades into a monolith
 * within weeks, and extraction later becomes archaeology. Review discipline is
 * not sufficient: the violations are individually reasonable and only
 * collectively fatal.
 *
 * Two further rules are checked here because they protect the same boundary:
 *
 *   - Shared is a technical kernel, not a bounded context. Every module may use
 *     it; it may use no module.
 *   - Every Eloquent model must be schema-qualified to its own module's schema.
 *     The database search_path spans all context schemas so `migrate:fresh`
 *     works, which means an unqualified table name would silently resolve into
 *     another context's schema.
 *
 * Usage:  php tools/check-module-boundaries.php [path-to-modules-dir]
 * Exit:   0 clean, 1 violations found.
 */

$modulesDir = $argv[1] ?? __DIR__.'/../services/core-api/modules';

if (! is_dir($modulesDir)) {
    fwrite(STDERR, "Modules directory not found: {$modulesDir}\n");
    exit(1);
}

/** Modules that are bounded contexts, and the database schema each owns. */
const SCHEMA_FOR_MODULE = [
    'Identity' => 'identity',
    'Profile' => 'profile',
    'Device' => 'device',
    'Administration' => 'admin',
];

const SHARED_MODULE = 'Shared';

$violations = [];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $relative = ltrim(str_replace(realpath($modulesDir), '', realpath($path)), DIRECTORY_SEPARATOR);
    $owningModule = explode(DIRECTORY_SEPARATOR, $relative)[0];
    $source = file_get_contents($path);

    if ($source === false) {
        continue;
    }

    $isTest = str_contains($relative, DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR);

    // --- Rule 1 and 2: cross-module imports ---------------------------------
    preg_match_all('/^\s*use\s+(Modules\\\\[A-Za-z0-9_\\\\]+)/m', $source, $matches);

    foreach ($matches[1] as $import) {
        $segments = explode('\\', $import);
        $targetModule = $segments[1] ?? null;

        if ($targetModule === null || $targetModule === $owningModule) {
            continue;
        }

        if ($owningModule === SHARED_MODULE) {
            // Shared is the kernel every module builds on. If it depended on a
            // context, every context would transitively depend on that one.
            $violations[] = [
                $relative,
                "Shared must not import from module {$targetModule} ({$import})",
            ];

            continue;
        }

        if ($targetModule === SHARED_MODULE) {
            continue;
        }

        $targetNamespace = $segments[2] ?? '';

        if ($targetNamespace !== 'Contracts') {
            $violations[] = [
                $relative,
                "imports {$import} — only Modules\\{$targetModule}\\Contracts\\* may cross a module boundary",
            ];
        }
    }

    // --- Rule 3: models are schema-qualified to their own module ------------
    if (! $isTest && preg_match('/protected\s+\$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $source, $m) === 1) {
        $table = $m[1];
        $expected = SCHEMA_FOR_MODULE[$owningModule] ?? null;

        if ($expected === null) {
            $violations[] = [$relative, "module {$owningModule} declares a table but owns no schema"];
        } elseif (! str_starts_with($table, $expected.'.')) {
            $violations[] = [
                $relative,
                "table '{$table}' is not in this module's schema ('{$expected}.'). "
                .'search_path spans every context schema, so an unqualified name resolves silently elsewhere',
            ];
        }
    }
}

if ($violations === []) {
    $count = iterator_count(new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
    ));

    fwrite(STDOUT, "Module boundaries clean ({$count} files checked).\n");
    exit(0);
}

fwrite(STDERR, "\nMODULE BOUNDARY VIOLATIONS\n".str_repeat('-', 72)."\n");

foreach ($violations as [$file, $message]) {
    fwrite(STDERR, "  {$file}\n    {$message}\n\n");
}

fwrite(STDERR, count($violations)." violation(s). See docs/architecture/03-repository-structure.md §4.\n");
exit(1);
