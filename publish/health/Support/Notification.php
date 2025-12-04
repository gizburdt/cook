<?php

namespace Spatie\Health\Notifications;
namespace App\Support\Health;

use Illuminate\Notifications\Messages\SlackMessage;
use Spatie\Health\Notifications\CheckFailedNotification;

class Notification extends CheckFailedNotification
{
    public function toDiscord(): SlackMessage
    {
        return $this->toSlack();
    }
}
