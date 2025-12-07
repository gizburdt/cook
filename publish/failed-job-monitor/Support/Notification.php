<?php

namespace App\Support\FailedJobMonitor;

use Awssat\Notifications\Messages\DiscordMessage;
use Spatie\FailedJobMonitor\Notification as VendorNotification;

class Notification extends VendorNotification
{
    public function toDiscord(): DiscordMessage
    {
        return (new DiscordMessage)
            ->content(__('A job failed at '.config('app.name')))
            ->embed(function ($embed) {
                $embed
                    ->color('#E01E5A')
                    ->title($this->event->job->resolveName())
                    ->description($this->event->exception->getMessage());
            });
    }
}
