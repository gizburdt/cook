<?php

namespace App\Support\Health;

use Illuminate\Notifications\Notifiable as NotifiableTrait;
use Spatie\Health\Notifications\Notifiable as VendorNotifiable;

class Notifiable extends VendorNotifiable
{
    use NotifiableTrait;

    public function routeNotificationForDiscord(): string
    {
        return config('health.notifications.discord.webhook_url');
    }
}
