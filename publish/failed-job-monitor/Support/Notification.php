<?php

namespace App\Support\FailedJobMonitor;

use Gizburdt\Talk\Discord\DiscordEmbed;
use Gizburdt\Talk\Discord\DiscordMessage;
use Spatie\FailedJobMonitor\Notification as VendorNotification;

class Notification extends VendorNotification
{
    public function toDiscord(): DiscordMessage
    {
        return DiscordMessage::make(__('A job failed at :app', ['app' => config('app.name')]))
            ->embed(function (DiscordEmbed $embed) {
                $embed
                    ->color('#E01E5A')
                    ->title($this->event->job->resolveName())
                    ->description($this->event->exception->getMessage());
            });
    }
}
