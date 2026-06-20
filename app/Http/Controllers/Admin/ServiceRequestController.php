<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest\UpdateServiceRequestStatusRequest;
use App\Models\Notification;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * Map DB status to the UI status shown in admin panel.
     * DB stores: pending | in_progress | completed | rejected
     * UI shows:  pending | processing  | approved  | rejected
     */
    private function toUiStatus(string $dbStatus): string
    {
        return match ($dbStatus) {
            'in_progress' => 'processing',
            'completed'   => 'approved',
            default       => $dbStatus,
        };
    }

    /**
     * Map UI status back to DB status before saving.
     */
    private function toDbStatus(string $uiStatus): string
    {
        return match ($uiStatus) {
            'processing' => 'in_progress',
            'approved'   => 'completed',
            default      => $uiStatus,
        };
    }

    public function index(Request $request)
    {
        $query = ServiceRequest::with(['association', 'user'])->latest();

        if ($status = $request->query('status')) {
            // Accept both UI and DB formats in the filter
            $dbStatus = $this->toDbStatus($status);
            $query->where('status', $dbStatus);
        }

        $requests = $query->get()->map(function ($req) {
            if ($req->association) {
                $name  = $req->association->association_name;
                $email = $req->association->email ?? '';
            } elseif ($req->user) {
                $name  = $req->user->full_name ?: ('مستخدم #' . $req->user->id);
                $email = $req->user->email ?? '';
            } else {
                $name  = 'مجهول';
                $email = '';
            }

            return [
                'id'               => $req->id,
                'requester_name'   => $name,
                'requester_email'  => $email,
                'association_name' => $name,   // kept for backward compat
                'association_email'=> $email,
                'title'            => $req->title,
                'service_type'     => $req->service_type,
                'details'          => $req->details,
                'budget'           => $req->budget,
                'preferred_date'   => $req->preferred_date,
                'status'           => $this->toUiStatus($req->status),
                'created_at'       => optional($req->created_at)->format('Y-m-d H:i') ?? '',
            ];
        });

        return response()->json($requests);
    }

    public function updateStatus(UpdateServiceRequestStatusRequest $request, $id)
    {
        $sr = ServiceRequest::findOrFail($id);
        $newDbStatus = $this->toDbStatus($request->status);
        $sr->update(['status' => $newDbStatus]);

        // Notify the requester when their service request is approved
        if ($newDbStatus === 'completed') {
            if ($sr->user_id) {
                Notification::create([
                    'user_id'      => $sr->user_id,
                    'title'        => 'تمت الموافقة على طلب الخدمة',
                    'body'         => "تمت الموافقة على طلبك: {$sr->title}",
                    'type'         => 'service_request_approved',
                    'related_id'   => $sr->id,
                    'related_type' => ServiceRequest::class,
                    'is_read'      => false,
                ]);
            } elseif ($sr->association_id) {
                Notification::create([
                    'association_id' => $sr->association_id,
                    'title'          => 'تمت الموافقة على طلب الخدمة',
                    'body'           => "تمت الموافقة على طلبك: {$sr->title}",
                    'type'           => 'service_request_approved',
                    'related_id'     => $sr->id,
                    'related_type'   => ServiceRequest::class,
                    'is_read'        => false,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب بنجاح']);
    }
}
