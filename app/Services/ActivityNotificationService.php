<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Log;

class ActivityNotificationService
{
    /**
     * Send notification to a specific user (e.g. Sales).
     */
    public static function notifyUser(User|int|string $user, array $data): void
    {
        try {
            $recipient = $user instanceof User ? $user : User::find($user);

            if (! $recipient || $recipient->status !== 'active') {
                return;
            }

            $recipient->notify(new ActivityNotification($data));
        } catch (\Throwable $e) {
            Log::error('Failed to send activity notification to user: ' . $e->getMessage(), [
                'user' => $user,
                'data' => $data,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Send notification to all active Admins and Super Admins.
     */
    public static function notifyAdmins(array $data): void
    {
        try {
            $recipients = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'super-admin']);
            })
            ->where('status', 'active')
            ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            foreach ($recipients as $recipient) {
                $recipient->notify(new ActivityNotification($data));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send activity notification: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
        }
    }
}
