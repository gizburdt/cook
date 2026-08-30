<?php

it('mail command offers every supported mailer', function () {
    expect(commandSource('Mail'))
        ->toContain("'postmark' => 'Postmark'")
        ->toContain("'resend' => 'Resend'")
        ->toContain("'mailgun' => 'Mailgun'")
        ->toContain("'gmail' => 'Gmail (SMTP)'");
});

it('mail command installs the package belonging to the mailer', function () {
    expect(commandSource('Mail'))
        ->toContain("\$this->packages['symfony/postmark-mailer'] = 'require';")
        ->toContain("\$this->packages['resend/resend-laravel'] = 'require';")
        ->toContain("\$this->packages['symfony/mailgun-mailer'] = 'require';")
        ->toContain("\$this->packages['symfony/http-client'] = 'require';");
});

it('mail command adds environment variables per mailer', function () {
    expect(commandSource('Mail'))
        ->toContain("'POSTMARK_API_KEY' => ''")
        ->toContain("'RESEND_API_KEY' => ''")
        ->toContain("'MAILGUN_DOMAIN' => ''")
        ->toContain("'MAILGUN_SECRET' => ''")
        ->toContain("'MAIL_HOST' => 'smtp.gmail.com'")
        ->toContain("'MAIL_PORT' => '587'");
});

it('mail command only writes config for mailgun', function () {
    expect(commandSource('Mail'))
        ->toContain("if (\$this->driver === 'mailgun') {")
        ->toContain('AddMailgunMailer::class')
        ->toContain('AddMailgunService::class');
});
