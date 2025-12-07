<?php

namespace App\Support\FailedJobMonitor;

use Awssat\Notifications\Messages\DiscordEmbed;
use Awssat\Notifications\Messages\DiscordMessage;
use Spatie\Health\Notifications\CheckFailedNotification;

class Notification extends CheckFailedNotification
{
    public function toDiscord(): DiscordMessage
    {
        $message = (new DiscordMessage)->content(
            trans('A job failed at '.config('app.name'))
        );

        $results = [
            'Exception message' => $this->event->exception->getMessage(),
            'Job class' => $this->event->job->resolveName(),
            'Job body' => $this->event->job->getRawBody(),
            'Exception' => $this->event->exception->getTraceAsString(),
        ];

        foreach ($results as $title => $content) {
            $message->embed(function (DiscordEmbed $embed) use ($title, $content) {
                $embed->title($title)->description($content);
            });
        }

        return $message;
    }
}
