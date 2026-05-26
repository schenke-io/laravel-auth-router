<?php

use Illuminate\Support\Facades\File;

arch('login providers')
    ->expect('SchenkeIo\LaravelAuthRouter\LoginProviders')
    ->toExtend('SchenkeIo\LaravelAuthRouter\Auth\BaseProvider')
    ->toHaveSuffix('Provider');

arch('contracts')
    ->expect('SchenkeIo\LaravelAuthRouter\Contracts')
    ->toBeInterfaces();

it('declares 1 to 3 groups in every test file', function () {
    $directory = new RecursiveDirectoryIterator(__DIR__.'/..');
    $iterator = new RecursiveIteratorIterator($directory);
    $offenders = [];

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }
        $filename = $file->getFilename();
        if (str_ends_with($filename, 'Test.php') || str_starts_with($filename, 'test_')) {
            if (in_array($filename, ['TestCase.php', 'Pest.php'], true)) {
                continue;
            }
            $contents = file_get_contents($file->getRealPath());

            // Count distinct group names declared at file level or per-test.
            preg_match_all(
                "/->group\(\s*((?:'[^']+'\s*,?\s*)+)\)/",
                $contents,
                $matches
            );

            preg_match_all(
                "/pest\(\)->group\(\s*((?:'[^']+'\s*,?\s*)+)\)/",
                $contents,
                $matches2
            );

            $allMatches = array_merge($matches[1] ?? [], $matches2[1] ?? []);

            $groups = collect($allMatches)
                ->flatMap(fn ($g) => preg_split("/\s*,\s*/", trim($g, ', ')))
                ->map(fn ($g) => trim($g, " '\""))
                ->filter()
                ->unique()
                ->values();

            $count = $groups->count();
            if ($count < 1 || $count > 3) {
                $offenders[$file->getPathname()] = $count;
            }
        }
    }

    expect($offenders)->toBe(
        [],
        "Every test file must declare 1 to 3 groups. Offenders:\n"
            .collect($offenders)->map(fn ($n, $f) => "  {$f}: {$n} groups")->implode("\n")
    );
})->group('GroupDiscipline');
