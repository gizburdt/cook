<?php

declare(strict_types=1);
use NunoMaduro\Essentials\Configurables\AggressivePrefetching;
use NunoMaduro\Essentials\Configurables\AutomaticallyEagerLoadRelationships;
use NunoMaduro\Essentials\Configurables\FakeSleep;
use NunoMaduro\Essentials\Configurables\ForceScheme;
use NunoMaduro\Essentials\Configurables\ImmutableDates;
use NunoMaduro\Essentials\Configurables\PreventStrayRequests;
use NunoMaduro\Essentials\Configurables\ProhibitDestructiveCommands;
use NunoMaduro\Essentials\Configurables\SetDefaultPassword;
use NunoMaduro\Essentials\Configurables\ShouldBeStrict;
use NunoMaduro\Essentials\Configurables\Unguard;

/**
 * https://github.com/nunomaduro/essentials/blob/main/config/essentials.php
 */
return [

    AggressivePrefetching::class => true,

    AutomaticallyEagerLoadRelationships::class => true,

    FakeSleep::class => true,

    ForceScheme::class => true,

    'environments' => [
        ForceScheme::class => ['production'],
    ],

    ImmutableDates::class => true,

    PreventStrayRequests::class => true,

    ProhibitDestructiveCommands::class => true,

    SetDefaultPassword::class => true,

    ShouldBeStrict::class => true,

    Unguard::class => true,

];
