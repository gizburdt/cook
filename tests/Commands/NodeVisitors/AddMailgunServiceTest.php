<?php

use Gizburdt\Cook\Commands\NodeVisitors\AddMailgunService;

function servicesConfig(): string
{
    return <<<'PHP'
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

];
PHP;
}

it('adds mailgun service to services config', function () {
    $result = createPhpParserHelper()->testParseContent(servicesConfig(), [
        new AddMailgunService,
    ]);

    expect($result)
        ->toContain("'domain' => env('MAILGUN_DOMAIN')")
        ->toContain("'secret' => env('MAILGUN_SECRET')")
        ->toContain("'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net')")
        ->toContain("'scheme' => 'https'");
});

it('adds mailgun service before the other services', function () {
    $result = createPhpParserHelper()->testParseContent(servicesConfig(), [
        new AddMailgunService,
    ]);

    expect($result)
        ->toMatch("/'mailgun' => \[[^\]]+\],\s*\n\s*\n\s*'postmark'/s");
});

it('keeps the leading docblock above the mailgun service', function () {
    $result = createPhpParserHelper()->testParseContent(servicesConfig(), [
        new AddMailgunService,
    ]);

    expect($result)
        ->toMatch("/Third Party Services\s*\n\s*\|-+\s*\n\s*\*\/\s*\n\s*\n\s*'mailgun'/s");
});

it('leaves the services config alone when mailgun is already configured', function () {
    $parser = createPhpParserHelper();

    $once = $parser->testParseContent(servicesConfig(), [new AddMailgunService]);

    $twice = $parser->testParseContent($once, [new AddMailgunService]);

    expect($twice)->toBe($once);
});

it('keeps blank lines around the services array', function () {
    $result = createPhpParserHelper()->testParseContent(servicesConfig(), [
        new AddMailgunService,
    ]);

    expect($result)
        ->toMatch("/return \[\s*\n\s*\n\s*\/\*/s")
        ->toMatch("/'resend' => \[[^\]]+\],\s*\n\s*\n\s*\];/s");
});
