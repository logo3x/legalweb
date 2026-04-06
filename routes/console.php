<?php

use App\Models\Reminder;
use App\Notifications\ReminderDueNotification;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:check-deadlines')->dailyAt('08:00');

Schedule::call(function () {
    $reminders = Reminder::with('user')
        ->where('is_completed', false)
        ->whereNotNull('remind_at')
        ->where('remind_at', '<=', now())
        ->where('remind_at', '>=', now()->subHour())
        ->get();

    foreach ($reminders as $reminder) {
        if ($reminder->user) {
            $reminder->user->notify(new ReminderDueNotification($reminder));
        }
    }
})->everyFiveMinutes()->name('send-reminders');

Schedule::command('app:sync-tyba-actuaciones')->dailyAt('03:00');
