<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddHealthSchedule;

it('adds health schedule command to console routes', function () {
    $parser = createPhpParserHelper();

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

it('adds heartbeat schedule command to console routes', function () {
    $parser = createPhpParserHelper();

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
        ->toContain('Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute()');
});

it('adds schedule use statement', function () {
    $parser = createPhpParserHelper();

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
    $parser = createPhpParserHelper();

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

it('adds heartbeat command use statement', function () {
    $parser = createPhpParserHelper();

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
        ->toContain('use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand');
});

it('does not add schedule use statement if it already exists', function () {
    $parser = createPhpParserHelper();

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
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
Schedule::command(RunHealthChecksCommand::class)->everyMinute();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect(substr_count($result, 'Schedule::command(RunHealthChecksCommand::class)'))
        ->toBe(1);
});

it('does not add heartbeat command if it already exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
Schedule::command(RunHealthChecksCommand::class)->everyMinute();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect(substr_count($result, 'Schedule::command(ScheduleCheckHeartbeatCommand::class)'))
        ->toBe(1);
});

it('preserves existing schedules when adding health schedule', function () {
    $parser = createPhpParserHelper();

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
        ->toContain('Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute()')
        ->toContain('Schedule::command(RunHealthChecksCommand::class)->everyMinute()');
});

it('adds blank line before health schedule', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send')->daily();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toMatch('/->daily\(\);\n\nSchedule::command\(RunHealthChecksCommand::class\)/s');
});

it('adds health before heartbeat schedule without blank line between', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('emails:send')->daily();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toMatch('/Schedule::command\(RunHealthChecksCommand::class\)->everyMinute\(\);\nSchedule::command\(ScheduleCheckHeartbeatCommand::class\)->everyMinute\(\);/s');
});

it('adds health schedule if only heartbeat exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toContain('Schedule::command(RunHealthChecksCommand::class)->everyMinute()')
        ->and(substr_count($result, 'Schedule::command(ScheduleCheckHeartbeatCommand::class)'))
        ->toBe(1);
});

it('adds heartbeat schedule if only health exists', function () {
    $parser = createPhpParserHelper();

    $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;

Schedule::command(RunHealthChecksCommand::class)->everyMinute();
PHP;

    $result = $parser->testParseContent($content, [
        AddHealthSchedule::class,
    ]);

    expect($result)
        ->toContain('Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute()')
        ->and(substr_count($result, 'Schedule::command(RunHealthChecksCommand::class)'))
        ->toBe(1);
});
