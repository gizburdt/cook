<?php

use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/cook-env-test-'.uniqid();

    mkdir($this->tempDir);

    $this->envPath = $this->tempDir.'/.env';

    $this->envExamplePath = $this->tempDir.'/.env.example';
});

afterEach(function () {
    if (file_exists($this->envPath)) {
        unlink($this->envPath);
    }

    if (file_exists($this->envExamplePath)) {
        unlink($this->envExamplePath);
    }

    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

it('adds new variables to env file', function () {
    file_put_contents($this->envPath, "APP_NAME=Laravel\n");

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'NEW_VAR' => 'value',
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('APP_NAME=Laravel')
        ->toContain('NEW_VAR=value');
});

it('does not duplicate existing variables', function () {
    file_put_contents($this->envPath, "APP_NAME=Laravel\nEXISTING_VAR=old\n");

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'EXISTING_VAR' => 'new',
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('EXISTING_VAR=old')
        ->not->toContain('EXISTING_VAR=new');
});

it('adds multiple variables at once', function () {
    file_put_contents($this->envPath, "APP_NAME=Laravel\n");

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'VAR_ONE' => 'one',
        'VAR_TWO' => 'two',
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('VAR_ONE=one')
        ->toContain('VAR_TWO=two');
});

it('skips null values', function () {
    file_put_contents($this->envPath, "APP_NAME=Laravel\n");

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'VALID_VAR' => 'value',
        'NULL_VAR' => null,
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('VALID_VAR=value')
        ->not->toContain('NULL_VAR');
});

it('skips integer keys', function () {
    file_put_contents($this->envPath, "APP_NAME=Laravel\n");

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'VALID_VAR' => 'value',
        0 => 'should_be_skipped',
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('VALID_VAR=value')
        ->not->toContain('should_be_skipped');
});

it('does nothing when file does not exist', function () {
    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'NEW_VAR' => 'value',
    ]);

    expect(file_exists($this->envPath))
        ->toBeFalse();
});

it('correctly detects existing variable with hasVariable', function () {
    $content = "APP_NAME=Laravel\nAPP_ENV=local\n";

    $parser = createEnvParser($this->tempDir);

    expect($parser->testHasVariable($content, 'APP_NAME'))
        ->toBeTrue()
        ->and($parser->testHasVariable($content, 'APP_ENV'))
        ->toBeTrue()
        ->and($parser->testHasVariable($content, 'NON_EXISTENT'))
        ->toBeFalse();
});

it('handles empty values correctly', function () {
    file_put_contents($this->envPath, "APP_NAME=Laravel\n");

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'EMPTY_VAR' => '',
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('EMPTY_VAR=');
});

it('adds blank line before new variables', function () {
    file_put_contents($this->envPath, 'APP_NAME=Laravel');

    $parser = createEnvParser($this->tempDir);

    $parser->testAddVariablesToFile($this->envPath, [
        'NEW_VAR' => 'value',
    ]);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toBe("APP_NAME=Laravel\n\nNEW_VAR=value\n");
});

function createEnvParser(string $tempDir): object
{
    return new class($tempDir)
    {
        use UsesEnvParser;

        protected Filesystem $files;

        public function __construct(protected string $tempDir)
        {
            $this->files = new Filesystem;
        }

        public function testAddVariablesToFile(string $file, array $variables): void
        {
            $this->addVariablesToFile($file, $variables);
        }

        public function testHasVariable(string $content, string $key): bool
        {
            return $this->hasVariable($content, $key);
        }
    };
}
