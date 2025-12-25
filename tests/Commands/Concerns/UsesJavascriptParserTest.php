<?php

use Gizburdt\Cook\Commands\Concerns\UsesJavascriptParser;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-js-test-'.uniqid();

    mkdir($this->tempDir);

    $this->viteConfigPath = $this->tempDir.'/vite.config.js';
});

afterEach(function () {
    if (file_exists($this->viteConfigPath)) {
        unlink($this->viteConfigPath);
    }

    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

it('adds input to vite config', function () {
    file_put_contents($this->viteConfigPath, <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
JS);

    $parser = createJavascriptParser($this->tempDir);

    $result = $parser->testAddInputToViteConfig(
        $this->viteConfigPath,
        'resources/css/filament/admin/theme.css'
    );

    $content = file_get_contents($this->viteConfigPath);

    expect($result)->toBeTrue()
        ->and($content)->toContain("'resources/css/filament/admin/theme.css'");
});

it('does not duplicate existing input', function () {
    file_put_contents($this->viteConfigPath, <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
JS);

    $parser = createJavascriptParser($this->tempDir);

    $result = $parser->testAddInputToViteConfig(
        $this->viteConfigPath,
        'resources/css/filament/admin/theme.css'
    );

    expect($result)->toBeFalse();
});

it('returns false when file does not exist', function () {
    $parser = createJavascriptParser($this->tempDir);

    $result = $parser->testAddInputToViteConfig(
        $this->viteConfigPath,
        'resources/css/filament/admin/theme.css'
    );

    expect($result)->toBeFalse()
        ->and(file_exists($this->viteConfigPath))->toBeFalse();
});

it('preserves existing config structure', function () {
    file_put_contents($this->viteConfigPath, <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
JS);

    $parser = createJavascriptParser($this->tempDir);

    $parser->testAddInputToViteConfig(
        $this->viteConfigPath,
        'resources/css/filament/admin/theme.css'
    );

    $content = file_get_contents($this->viteConfigPath);

    expect($content)
        ->toContain("import { defineConfig } from 'vite';")
        ->toContain("import laravel from 'laravel-vite-plugin';")
        ->toContain("import tailwindcss from '@tailwindcss/vite';")
        ->toContain('tailwindcss()')
        ->toContain('refresh: true');
});

it('adds input after first entry in input array', function () {
    file_put_contents($this->viteConfigPath, <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
JS);

    $parser = createJavascriptParser($this->tempDir);

    $parser->testAddInputToViteConfig(
        $this->viteConfigPath,
        'resources/css/filament/admin/theme.css'
    );

    $content = file_get_contents($this->viteConfigPath);

    $appCssPos = strpos($content, "'resources/css/app.css'");
    $themePos = strpos($content, "'resources/css/filament/admin/theme.css'");
    $appJsPos = strpos($content, "'resources/js/app.js'");

    expect($appCssPos)->toBeLessThan($themePos)
        ->and($themePos)->toBeLessThan($appJsPos);
});

function createJavascriptParser(string $tempDir): object
{
    return new class($tempDir)
    {
        use UsesJavascriptParser;

        protected Filesystem $files;

        public function __construct(protected string $tempDir)
        {
            $this->files = new Filesystem;
        }

        public function testAddInputToViteConfig(string $file, string $input): bool
        {
            return $this->addInputToViteConfig($file, $input);
        }
    };
}
