<?php

use Gizburdt\Cook\Enums\MfaMethod;

it('exposes prompt options', function () {
    expect(MfaMethod::options())
        ->toBe(['app' => 'App', 'email' => 'Email']);
});

it('exposes app metadata', function () {
    expect(MfaMethod::App->contracts())
        ->toBe([
            'Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication',
            'Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery',
        ])
        ->and(MfaMethod::App->traits())
        ->toBe([
            'Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication',
            'Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery',
        ])
        ->and(MfaMethod::App->panelClass())
        ->toBe('Filament\Auth\MultiFactor\App\AppAuthentication')
        ->and(MfaMethod::App->panelRecoverable())
        ->toBeTrue()
        ->and(MfaMethod::App->migration())
        ->toBe('add_app_authentication_to_users_table');
});

it('exposes email metadata', function () {
    expect(MfaMethod::Email->contracts())
        ->toBe([
            'Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication',
        ])
        ->and(MfaMethod::Email->traits())
        ->toBe([
            'Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication',
        ])
        ->and(MfaMethod::Email->panelClass())
        ->toBe('Filament\Auth\MultiFactor\Email\EmailAuthentication')
        ->and(MfaMethod::Email->panelRecoverable())
        ->toBeFalse()
        ->and(MfaMethod::Email->migration())
        ->toBe('add_email_authentication_to_users_table');
});

it('detects app from implemented interfaces by short name', function () {
    expect(MfaMethod::detect(['HasAppAuthentication', 'HasAppAuthenticationRecovery']))
        ->toBe([MfaMethod::App]);
});

it('detects email from implemented interfaces', function () {
    expect(MfaMethod::detect(['HasEmailAuthentication']))
        ->toBe([MfaMethod::Email]);
});

it('detects both when both are implemented', function () {
    expect(MfaMethod::detect(['HasAppAuthentication', 'HasEmailAuthentication']))
        ->toBe([MfaMethod::App, MfaMethod::Email]);
});

it('detects nothing when no mfa interfaces are implemented', function () {
    expect(MfaMethod::detect(['FilamentUser', 'HasApiTokens']))
        ->toBe([]);
});

it('detects from fully qualified interface names', function () {
    expect(MfaMethod::detect(['Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication']))
        ->toBe([MfaMethod::App]);
});
