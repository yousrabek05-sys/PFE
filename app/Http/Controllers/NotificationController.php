<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    // Get all notifications for current user
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return response()->json([
            'status'       => 'success',
            'unread_count' => $unreadCount,
            'data'         => $notifications
        ]);
    }

    // Mark one notification as read
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notification marked as read'
        ]);
    }

    // MARK ALL AS READ 
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }

    // Send a notification
    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'type'    => 'required|string',
            'channel' => 'required|in:in_app,email,sms',
        ]);

        $user = $request->user();
        
        if (!in_array($user->role, ['doctor', 'assistant'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $targetUser = User::find($request->user_id);

        switch ($request->channel) {

            case 'in_app':
                $notification = $this->sendInApp(
                    $request->user_id,
                    $request->message,
                    $request->type
                );
                break;
                case 'email':
                $notification = $this->sendEmail(
                    $targetUser,
                    $request->message,
                    $request->type
                );
                break;

            case 'sms':
                $notification = $this->sendSms(
                    $targetUser,
                    $request->message,
                    $request->type
                );
                break;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Notification sent',
            'data'    => $notification
        ]);
    }

    // Send In-App Notification
    private function sendInApp($userId, $message, $type)
    {
        return Notification::create([
            'user_id' => $userId,
            'message' => $message,
            'type'    => $type,
            'date'    => now(),
            'is_read' => false,
            'channel' => 'in_app',
            'status'  => 'sent',
            'sent_at' => now(),
        ]);
    }

    // Send Email Notification
    private function sendEmail(User $user, $message, $type)
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'message' => $message,
            'type'    => $type,
            'date'    => now(),
            'is_read' => false,
            'channel' => 'email',
            'status'  => 'pending', 
            'sent_at' => null,
        ]);

        try {
            Mail::raw($message, function ($mail) use ($user) {
                $mail->to($user->email)
                     ->subject('Cabinet Dentaire — New Notification');
            });

            $notification->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            $notification->update(['status' => 'failed']);
        }

        return $notification;
    }

    // Send SMS Notification
    private function sendSms(User $user, $message, $type)
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'message' => $message,
            'type'    => $type,
            'date'    => now(),
            'is_read' => false,
            'channel' => 'sms',
            'status'  => 'pending',
            'sent_at' => null,
        ]);

        if (!$user->phone) {
            $notification->update(['status' => 'failed']);
            return $notification;
        }

        try {
            $twilio = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create(
                $user->phone, 
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message,
                ]
            );

            $notification->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            $notification->update(['status' => 'failed']);
        }

        return $notification;
    }
}