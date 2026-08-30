<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddMailgunMailer;
use Gizburdt\Cook\Commands\NodeVisitors\AddMailgunService;

use function Laravel\Prompts\select;

class Mail extends Command
{
    use InstallsPackages;
    use UsesEnvParser;
    use UsesPhpParser;

    protected $signature = 'cook:mail {--force} {--skip-pint}';

    protected $description = 'Install mail';

    protected ?string $docs = null;

    protected string $driver;

    protected array $packages = [];

    public function handle(): void
    {
        $this->driver = select('Which mailer?', [
            'postmark' => 'Postmark',
            'resend' => 'Resend',
            'mailgun' => 'Mailgun',
            'gmail' => 'Gmail (SMTP)',
        ], 'postmark');

        $this->setupDriver();

        $this->tryInstallPackages();

        $this->addCode();

        $this->openDocs();
    }

    protected function setupDriver(): void
    {
        if ($this->driver === 'postmark') {
            $this->packages['symfony/postmark-mailer'] = 'require';

            $this->docs = 'https://laravel.com/docs/12.x/mail#postmark-driver';
        }

        if ($this->driver === 'resend') {
            $this->packages['resend/resend-laravel'] = 'require';

            $this->docs = 'https://resend.com/docs/send-with-laravel';
        }

        if ($this->driver === 'mailgun') {
            $this->packages['symfony/http-client'] = 'require';
            $this->packages['symfony/mailgun-mailer'] = 'require';

            $this->docs = 'https://laravel.com/docs/12.x/mail#mailgun-driver';
        }

        if ($this->driver === 'gmail') {
            $this->docs = 'https://support.google.com/accounts/answer/185833';
        }
    }

    protected function addCode(): void
    {
        $this->components->info('Adding environment variables');

        $this->addEnvVariables($this->envVariables());

        $this->components->bulletList(
            collect($this->envVariables())->map(fn ($value, $key) => "{$key}={$value}")->values()->all()
        );

        $this->components->warn('Existing variables are never overwritten; verify the values above.');

        if ($this->driver === 'mailgun') {
            $this->components->info('Adding config');

            $this->addConfig();
        }

        $this->runPint();
    }

    /**
     * @return array<string, string>
     */
    protected function envVariables(): array
    {
        $variables = match ($this->driver) {
            'postmark' => [
                'MAIL_MAILER' => 'postmark',
                'POSTMARK_API_KEY' => '',
            ],
            'resend' => [
                'MAIL_MAILER' => 'resend',
                'RESEND_API_KEY' => '',
            ],
            'mailgun' => [
                'MAIL_MAILER' => 'mailgun',
                'MAILGUN_DOMAIN' => '',
                'MAILGUN_SECRET' => '',
                'MAILGUN_ENDPOINT' => 'api.mailgun.net',
            ],
            'gmail' => [
                'MAIL_MAILER' => 'smtp',
                'MAIL_SCHEME' => 'null',
                'MAIL_HOST' => 'smtp.gmail.com',
                'MAIL_PORT' => '587',
                'MAIL_USERNAME' => '',
                'MAIL_PASSWORD' => '',
            ],
            default => [],
        };

        return array_merge($variables, [
            'MAIL_FROM_ADDRESS' => '"hello@example.com"',
            'MAIL_FROM_NAME' => '"${APP_NAME}"',
        ]);
    }

    protected function addConfig(): void
    {
        $this->applyPhpVisitors(config_path('mail.php'), [
            AddMailgunMailer::class,
        ]);

        $this->applyPhpVisitors(config_path('services.php'), [
            AddMailgunService::class,
        ]);
    }
}
