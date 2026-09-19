<?php

namespace App\Support\Health;

use Gizburdt\Talk\Discord\DiscordEmbed;
use Gizburdt\Talk\Discord\DiscordMessage;
use Spatie\Health\Enums\Status;
use Spatie\Health\Notifications\CheckFailedNotification;

class Notification extends CheckFailedNotification
{
    public function toDiscord(): DiscordMessage
    {
        $message = DiscordMessage::make(
            trans('health::notifications.check_failed_slack_message', $this->transParameters())
        );

        foreach ($this->results as $result) {
            $message->embed(function (DiscordEmbed $embed) use ($result) {
                $embed
                    ->color(Status::from($result->status)->getSlackColor())
                    ->title($result->check->getLabel())
                    ->description($result->getNotificationMessage());
            });
        }

        return $message;
    }
}
