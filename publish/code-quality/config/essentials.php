<?php

declare(strict_types=1);

/**
 * https://github.com/nunomaduro/essentials/blob/main/config/essentials.php
 */
return [

    NunoMaduro\Essentials\Configurables\AggressivePrefetching::class => true,

    NunoMaduro\Essentials\Configurables\AutomaticallyEagerLoadRelationships::class => true,

    NunoMaduro\Essentials\Configurables\FakeSleep::class => true,

    NunoMaduro\Essentials\Configurables\ForceScheme::class => true,

    'environments' => [
        NunoMaduro\Essentials\Configurables\ForceScheme::class => ['production'],
    ],

    NunoMaduro\Essentials\Configurables\ImmutableDates::class => true,

    NunoMaduro\Essentials\Configurables\PreventStrayRequests::class => true,

    NunoMaduro\Essentials\Configurables\ProhibitDestructiveCommands::class => true,

    NunoMaduro\Essentials\Configurables\SetDefaultPassword::class => true,

    NunoMaduro\Essentials\Configurables\ShouldBeStrict::class => true,

    NunoMaduro\Essentials\Configurables\Unguard::class => true,

];
