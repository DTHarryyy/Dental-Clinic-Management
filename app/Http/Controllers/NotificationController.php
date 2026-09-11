<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->limit(10)
            ->get(['id', 'type', 'data', 'read_at', 'created_at'])
            ->map(function (DatabaseNotification $notification): array {
                $when = $notification->data['scheduled_start_at'] ?? $notification->data['requested_start_at'] ?? null;
                $start = filled($when) ? \Carbon\CarbonImmutable::parse($when)->setTimezone('Asia/Manila') : null;
                $type = $notification->data['type'] ?? 'appointment_reminder';
                $title = $notification->data['title'] ?? match ($type) {
                    'appointment_requested' => 'New appointment request',
                    'appointment_change_requested' => 'Appointment change request',
                    default => 'Upcoming appointment',
                };

                return [
                    'id' => $notification->id,
                    'type' => $type,
                    'title' => $title,
                    'patient_name' => $notification->data['patient_name'] ?? 'Patient appointment',
                    'services' => $notification->data['services'] ?? ($notification->data['message'] ?? 'Dental appointment'),
                    'scheduled_at' => $start?->format('M j, Y \a\t g:i A') ?? 'Schedule unavailable',
                    'read' => $notification->read_at !== null,
                    'open_url' => route('notifications.open', $notification),
                    'read_url' => route('notifications.read', $notification),
                ];
            });

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $notification = $this->ownedNotification($request, $notification);
        $notification->markAsRead();

        if (isset($notification->data['url'])) {
            return redirect($notification->data['url']);
        }

        $appointmentId = (int) ($notification->data['appointment_id'] ?? 0);

        return redirect()->route('appointments.index', array_filter([
            'appointment' => $appointmentId ?: null,
        ]));
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->ownedNotification($request, $notification)->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    private function ownedNotification(Request $request, DatabaseNotification $notification): DatabaseNotification
    {
        abort_unless(
            $notification->notifiable_type === $request->user()->getMorphClass()
                && (string) $notification->notifiable_id === (string) $request->user()->getKey(),
            404,
        );

        return $notification;
    }
}
