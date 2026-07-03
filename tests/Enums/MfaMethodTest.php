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
        ->toBeTrue();
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
        ->toBeFalse();
});
