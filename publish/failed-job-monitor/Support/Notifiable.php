<?php

namespace App\Support\FailedJobMonitor;

use Illuminate\Notifications\Notifiable as NotifiableTrait;
use Spatie\FailedJobMonitor\Notifiable as VendorNotifiable;

class Notifiable extends VendorNotifiable
{
    use NotifiableTrait;

    public function routeNotificationForDiscord(): string
    {
        return config('failed-job-monitor.discord.webhook_url');
    }
}
