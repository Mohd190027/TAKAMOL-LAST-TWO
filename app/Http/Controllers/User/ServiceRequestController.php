<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest\StoreServiceRequestRequest;
use App\Http\Requests\ServiceRequest\UpdateUserServiceRequestRequest;
use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    private function resolveActorIds(): array
    {
        $userId        = null;
        $associationId = null;

        if (Auth::check()) {
            $pk = Auth::user()?->getAttribute('id');
            if (is_numeric($pk)) {
                $userId = (int) $pk;
            }
        }

        $assocSession = session('association');
        if (is_array($assocSession) && isset($assocSession['id']) && is_numeric($assocSession['id'])) {
            $associationId = (int) $assocSession['id'];
        }

        return [$userId, $associationId];
    }

    // Normalize DB status → UI status for the user-facing side
    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'in_progress' => 'review',
            'completed'   => 'approved',
            default       => $status, // pending, rejected
        };
    }

    public function index()
    {
        [$userId, $associationId] = $this->resolveActorIds();

        $query = ServiceRequest::query();
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($associationId) {
            $query->where('association_id', $associationId);
        } else {
            return response()->json([]);
        }

        $typeLabels = [
            'units'       => 'بناء وحدات/أنظمة',
            'training'    => 'تدريب المتطوعين',
            'initiatives' => 'تنسيق المبادرات',
            'consulting'  => 'استشارات متخصصة',
            'other'       => 'طلب آخر',
        ];

        $requests = $query->orderByDesc('id')->get()->map(function ($req) use ($typeLabels) {
            return [
                'id'        => $req->id,
                'type'      => $req->service_type,
                'typeLabel' => $typeLabels[$req->service_type] ?? 'طلب خدمة',
                'title'     => $req->title,
                'details'   => $req->details,
                'date'      => $req->created_at ? $req->created_at->translatedFormat('d M Y') : null,
                'budget'    => $req->budget,
                'status'    => $this->normalizeStatus($req->status),
            ];
        });

        return response()->json($requests);
    }

    public function store(StoreServiceRequestRequest $request)
    {
        try {
            $validated = $request->validated();

            [$userId, $associationId] = $this->resolveActorIds();

            if (!$userId && !$associationId) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
            }

            $sr = ServiceRequest::create([
                'user_id'        => $userId,
                'association_id' => $associationId,
                'service_type'   => $validated['service_type'],
                'title'          => $validated['title'],
                'details'        => $validated['details'],
                'budget'         => $validated['budget'] ?? 0,
                'preferred_date' => $validated['preferred_date'] ?? null,
                'status'         => 'pending',
            ]);

            // Notify the first admin
            $adminId = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->value('id');
            if ($adminId) {
                $requesterLabel = $associationId
                    ? (session('association.name') ?? 'جمعية')
                    : (Auth::user()?->full_name ?? 'مستخدم');

                Notification::create([
                    'user_id'      => $adminId,
                    'title'        => 'طلب خدمة جديد',
                    'body'         => "وصل طلب خدمة جديد من {$requesterLabel}: {$sr->title}",
                    'type'         => 'service_request_created',
                    'related_id'   => $sr->id,
                    'related_type' => ServiceRequest::class,
                ]);
            }

            return response()->json(['success' => true, 'message' => 'تم إرسال طلبك بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function update(UpdateUserServiceRequestRequest $request, $id)
    {
        try {
            [$userId, $associationId] = $this->resolveActorIds();

            $query = ServiceRequest::where('id', $id)->where('status', 'pending'); // only editable when pending
            if ($userId) {
                $query->where('user_id', $userId);
            } elseif ($associationId) {
                $query->where('association_id', $associationId);
            } else {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
            }

            $sr = $query->firstOrFail();

            $validated = $request->validated();

            $sr->update([
                'service_type'   => $validated['service_type'],
                'title'          => $validated['title'],
                'details'        => $validated['details'],
                'budget'         => $validated['budget'] ?? 0,
                'preferred_date' => $validated['preferred_date'] ?? null,
            ]);

            return response()->json(['success' => true, 'message' => 'تم تعديل الطلب بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود أو لا يمكن تعديله'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            [$userId, $associationId] = $this->resolveActorIds();

            $query = ServiceRequest::where('id', $id);
            if ($userId) {
                $query->where('user_id', $userId);
            } elseif ($associationId) {
                $query->where('association_id', $associationId);
            } else {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
            }

            $query->firstOrFail()->delete();

            return response()->json(['success' => true, 'message' => 'تم حذف الطلب']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }
}
