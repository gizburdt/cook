<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddBackupsSchedule;

it('adds backup clean command to console routes', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect($result)
        ->toContain("Schedule::command('backup:clean')->daily()->at('02:00')");
});

it('adds backup run command to console routes', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect($result)
        ->toContain("Schedule::command('backup:run')->daily()->at('03:00')");
});

it('adds schedule use statement', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect($result)
        ->toContain('use Illuminate\Support\Facades\Schedule');
});

it('does not add schedule use statement if it already exists', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect(substr_count($result, 'use Illuminate\Support\Facades\Schedule'))
        ->toBe(1);
});

it('does not add backup clean command if it already exists', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('02:00');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect(substr_count($result, "Schedule::command('backup:clean')"))
        ->toBe(1);
});

it('does not add backup run command if it already exists', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:run')->daily()->at('03:00');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect(substr_count($result, "Schedule::command('backup:run')"))
        ->toBe(1);
});

it('adds only missing backup commands', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('02:00');
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect($result)
        ->toContain("Schedule::command('backup:clean')")
        ->toContain("Schedule::command('backup:run')->daily()->at('03:00')");

    expect(substr_count($result, "Schedule::command('backup:clean')"))
        ->toBe(1);
});

it('preserves existing schedules when adding backup schedules', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send')->daily();
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect($result)
        ->toContain("Schedule::command('emails:send')->daily()")
        ->toContain("Schedule::command('backup:clean')->daily()->at('02:00')")
        ->toContain("Schedule::command('backup:run')->daily()->at('03:00')");
});

it('handles nested method calls when detecting existing backup commands', function () {
    $parser = createAddBackupsScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('04:00')->withoutOverlapping();
Schedule::command('backup:run')->daily()->at('05:00')->withoutOverlapping();
PHP;

    $result = $parser->testParseContent($content, [
        AddBackupsSchedule::class,
    ]);

    expect(substr_count($result, "Schedule::command('backup:clean')"))->toBe(1)
        ->and(substr_count($result, "Schedule::command('backup:run')"))->toBe(1);
});

function createAddBackupsScheduleParser(): object
{
    return new class
    {
        use UsesPhpParser;

        public function testParseContent(string $content, array $visitors): string
        {
            return $this->parseContent($content, $visitors);
        }
    };
}
