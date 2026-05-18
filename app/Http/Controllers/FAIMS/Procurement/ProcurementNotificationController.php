<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Services\FAIMS\Procurement\NotificationClass;
use Illuminate\Http\Request;

class ProcurementNotificationController extends Controller
{
    public function __construct(protected NotificationClass $notifications)
    {
    }

    public function index(Request $request)
    {
        $result = $this->notifications->mentionNotifications($request);
        $status = $result['_status'] ?? 200;
        unset($result['_status']);

        return response()->json($result, $status);
    }

    public function update(string $notificationId, Request $request)
    {
        $result = $this->notifications->markMentionNotificationRead($notificationId, $request);
        $status = $result['_status'] ?? 200;
        unset($result['_status']);

        return response()->json($result, $status);
    }
}
