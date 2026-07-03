<?php

namespace Gizburdt\Cook\Enums;

enum MfaMethod: string
{
    case App = 'app';

    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::App => 'App',
            self::Email => 'Email',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $method) {
            $options[$method->value] = $method->label();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public function contracts(): array
    {
        return match ($this) {
            self::App => [
                'Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication',
                'Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery',
            ],
            self::Email => [
                'Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function traits(): array
    {
        return match ($this) {
            self::App => [
                'Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication',
                'Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery',
            ],
            self::Email => [
                'Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication',
            ],
        };
    }

    public function panelClass(): string
    {
        return match ($this) {
            self::App => 'Filament\Auth\MultiFactor\App\AppAuthentication',
            self::Email => 'Filament\Auth\MultiFactor\Email\EmailAuthentication',
        };
    }

    public function panelRecoverable(): bool
    {
        return $this === self::App;
    }
}
