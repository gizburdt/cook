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

    public function migration(): string
    {
        return match ($this) {
            self::App => 'add_app_authentication_to_users_table',
            self::Email => 'add_email_authentication_to_users_table',
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

    /**
     * @param  array<int, string>  $interfaces
     */
    public function isImplementedBy(array $interfaces): bool
    {
        $required = self::basename($this->contracts()[0]);

        $implemented = array_map(fn (string $interface): string => self::basename($interface), $interfaces);

        return in_array($required, $implemented, true);
    }

    /**
     * @param  array<int, string>  $interfaces
     * @return array<int, self>
     */
    public static function detect(array $interfaces): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $method): bool => $method->isImplementedBy($interfaces)
        ));
    }

    protected static function basename(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
