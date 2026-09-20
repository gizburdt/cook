<?php

declare(strict_types=1);

/**
 * Fills the Composer dist cache straight from git.
 *
 * Packagist hands out every dist for this project as a GitHub zipball, and Claude
 * Code on the web only reaches api.github.com for repositories attached to the
 * session, so none of those downloads succeed there. Plain git reads of public
 * repositories are served, so each package's zip is rebuilt from its own commit
 * and written to the cache, where `composer install` picks it up and installs
 * from dist as usual.
 *
 * Packages this cannot build are left alone: Composer falls back to installing
 * them from source, which is slower and far bigger on disk, but still correct.
 */
const CONCURRENCY = 12;

const DIST_URL_PATTERN = '#^https://api\.github\.com/repos/([^/]+/[^/]+)/zipball/([0-9a-f]{40})$#';

/**
 * The packages that still need a cache entry.
 *
 * @return list<array{name: string, repository: string, reference: string, target: string}>
 */
function pendingPackages(string $cacheDirectory): array
{
    $lock = json_decode((string) file_get_contents(getcwd().'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);

    $pending = [];

    foreach ([...$lock['packages'] ?? [], ...$lock['packages-dev'] ?? []] as $package) {
        if (! preg_match(DIST_URL_PATTERN, $package['dist']['url'] ?? '', $matches)) {
            continue;
        }

        // Composer looks the entry up by the hash of the URL it would have downloaded.
        $target = sprintf('%s/files/%s/%s.zip', $cacheDirectory, $package['name'], sha1($package['dist']['url']));

        if (file_exists($target)) {
            continue;
        }

        $pending[] = [
            'name' => $package['name'],
            'repository' => $matches[1],
            'reference' => $matches[2],
            'target' => $target,
        ];
    }

    return $pending;
}

/**
 * Rebuilds one package's zip from a shallow fetch of the commit it is locked to.
 *
 * `git archive` honors the export-ignore rules in .gitattributes, just like the
 * zipball GitHub would have served, so the result holds the same files.
 *
 * @param  array{name: string, repository: string, reference: string, target: string}  $package
 */
function buildCommand(array $package): string
{
    $directory = sys_get_temp_dir().'/prime-composer-cache-'.sha1($package['target']);
    $prefix = str_replace('/', '-', $package['name']).'-'.substr($package['reference'], 0, 7).'/';

    return implode(' && ', [
        sprintf('rm -rf %s', escapeshellarg($directory)),
        sprintf('mkdir -p %s %s', escapeshellarg($directory), escapeshellarg(dirname($package['target']))),
        sprintf('git init --quiet %s', escapeshellarg($directory)),
        sprintf(
            'git -C %s fetch --quiet --depth 1 %s %s',
            escapeshellarg($directory),
            escapeshellarg("https://github.com/{$package['repository']}.git"),
            escapeshellarg($package['reference']),
        ),
        sprintf(
            'git -C %s archive --format=zip --prefix=%s --output=%s FETCH_HEAD',
            escapeshellarg($directory),
            escapeshellarg($prefix),
            escapeshellarg($package['target']),
        ),
        sprintf('rm -rf %s', escapeshellarg($directory)),
    ]);
}

$cacheDirectory = rtrim((string) (getenv('COMPOSER_CACHE_DIR') ?: getenv('HOME').'/.cache/composer'), '/');

$pending = pendingPackages($cacheDirectory);
$total = count($pending);

if ($total === 0) {
    echo "Composer dist cache is complete.\n";

    exit(0);
}

echo "Building {$total} dist cache entries...\n";

/** @var array<int, array{process: resource, errors: string, package: array{name: string, repository: string, reference: string, target: string}}> $running */
$running = [];
$failed = [];
$built = 0;
$slot = 0;

while ($pending !== [] || $running !== []) {
    while (count($running) < CONCURRENCY && $pending !== []) {
        $package = array_shift($pending);
        $errors = tempnam(sys_get_temp_dir(), 'prime-composer-cache-');

        // stderr goes to a file rather than a pipe so a noisy failure cannot fill
        // the buffer and deadlock a worker we are not reading from yet.
        $process = proc_open(
            ['bash', '-c', buildCommand($package)],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', $errors, 'w']],
            $pipes,
        );

        if (! is_resource($process)) {
            $failed[$package['name']] = 'unable to start git';

            continue;
        }

        $running[$slot++] = ['process' => $process, 'errors' => $errors, 'package' => $package];
    }

    foreach ($running as $key => $worker) {
        if (proc_get_status($worker['process'])['running']) {
            continue;
        }

        unset($running[$key]);

        $name = $worker['package']['name'];

        if (proc_close($worker['process']) === 0) {
            $built++;

            echo sprintf("  [%d/%d] %s\n", $built + count($failed), $total, $name);
        } else {
            $failed[$name] = trim((string) file_get_contents($worker['errors'])) ?: 'git failed';

            echo sprintf("  [%d/%d] %s — FAILED, Composer will fall back to source\n", $built + count($failed), $total, $name);
        }

        unlink($worker['errors']);
    }

    if ($running !== []) {
        usleep(50_000);
    }
}

echo "Built {$built} of {$total} dist cache entries.\n";

foreach ($failed as $name => $error) {
    fwrite(STDERR, "{$name}: {$error}\n");
}
