<?php

use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthSchedule;

it('adds health schedule command to console routes', function () {
    $parser = createAddHealthScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toContain('Schedule::command(RunHealthChecksCommand::class)->everyMinute()');
});

it('adds schedule use statement', function () {
    $parser = createAddHealthScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toContain('use Illuminate\Support\Facades\Schedule');
});

it('adds health command use statement', function () {
    $parser = createAddHealthScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toContain('use Spatie\Health\Commands\RunHealthChecksCommand');
});

it('does not add schedule use statement if it already exists', function () {
    $parser = createAddHealthScheduleParser();

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
        AddHealthSchedule::class,
    ]);

    expect(substr_count($result, 'use Illuminate\Support\Facades\Schedule'))
        ->toBe(1);
});

it('does not add health command if it already exists', function () {
    $parser = createAddHealthScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;

Schedule::command(RunHealthChecksCommand::class)->everyMinute();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect(substr_count($result, 'Schedule::command(RunHealthChecksCommand::class)'))
        ->toBe(1);
});

it('preserves existing schedules when adding health schedule', function () {
    $parser = createAddHealthScheduleParser();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send')->daily();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toContain("Schedule::command('emails:send')->daily()")
        ->toContain('Schedule::command(RunHealthChecksCommand::class)->everyMinute()');
});

function createAddHealthScheduleParser(): object
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
