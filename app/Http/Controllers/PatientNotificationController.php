<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class PatientNotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('patient.notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(12),
        ]);
    }

    public function open(Request $request, DatabaseNotification $notification)
    {
        $notification = $this->owned($request, $notification);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('patient.notifications.index'));
    }

    public function read(Request $request, DatabaseNotification $notification)
    {
        $this->owned($request, $notification)->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    private function owned(Request $request, DatabaseNotification $notification): DatabaseNotification
    {
        abort_unless(
            $notification->notifiable_type === $request->user()->getMorphClass()
                && (string) $notification->notifiable_id === (string) $request->user()->getKey(),
            404,
        );

        return $notification;
    }
}
