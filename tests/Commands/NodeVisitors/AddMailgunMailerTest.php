<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddMailgunMailer;

function mailConfig(): string
{
    return <<<'PHP'
<?php

return [

    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

    ],

];
PHP;
}

it('adds mailgun mailer to mail config', function () {
    $result = createPhpParserHelper()->testParseContent(mailConfig(), [
        new AddMailgunMailer,
    ]);

    expect($result)
        ->toContain("'mailgun' => [")
        ->toContain("'transport' => 'mailgun'");
});

it('adds mailgun mailer before the ses mailer', function () {
    $result = createPhpParserHelper()->testParseContent(mailConfig(), [
        new AddMailgunMailer,
    ]);

    expect($result)
        ->toMatch("/'mailgun' => \[[^\]]+\],\s*\n\s*\n\s*'ses'/s");
});

it('does not touch the mailer list of aggregate mailers', function () {
    $result = createPhpParserHelper()->testParseContent(mailConfig(), [
        new AddMailgunMailer,
    ]);

    expect($result)
        ->toMatch("/'failover' => \[\s*\n\s*'transport' => 'failover',\s*\n\s*'mailers' => \[\s*\n\s*'smtp',\s*\n\s*'log',\s*\n\s*\],/s")
        ->and(substr_count($result, "'transport' => 'mailgun'"))->toBe(1);
});

it('leaves the mail config alone when mailgun is already configured', function () {
    $parser = createPhpParserHelper();

    $once = $parser->testParseContent(mailConfig(), [new AddMailgunMailer]);

    $twice = $parser->testParseContent($once, [new AddMailgunMailer]);

    expect($twice)->toBe($once);
});

it('preserves comments inside existing mailers', function () {
    $result = createPhpParserHelper()->testParseContent(mailConfig(), [
        new AddMailgunMailer,
    ]);

    expect($result)
        ->toContain("// 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID')");
});

it('keeps blank lines between all mailers', function () {
    $result = createPhpParserHelper()->testParseContent(mailConfig(), [
        new AddMailgunMailer,
    ]);

    expect($result)
        ->toMatch("/'mailers' => \[\s*\n\s*\n\s*'smtp'/s")
        ->toMatch("/'smtp' => \[[^\]]+\],\s*\n\s*\n\s*'mailgun'/s")
        ->toMatch("/'ses' => \[[^\]]+\],\s*\n\s*\n\s*'postmark'/s");
});
