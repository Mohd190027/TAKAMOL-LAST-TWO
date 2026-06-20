<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JointProject\StoreJointProjectRequest;
use App\Http\Requests\JointProject\UpdateJointProjectRequest;
use App\Models\JointProject;
use App\Models\JointProjectUpdate;
use App\Models\AssociationCategory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JointProjectController extends Controller
{
    /**
     * GET /api/joint-projects
     * Returns all projects with stats + filters (category_id, status, search)
     */
    public function index(Request $request)
    {
        $query = JointProject::with(['category', 'updates'])->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $projects = $query->get()->map(fn($p) => $this->format($p));

        // Optimized Stats queries (prevents loading all records into memory)
        $total        = JointProject::count();
        $completed    = JointProject::where('status', 'completed')->count();
        $canceled     = JointProject::where('status', 'canceled')->count();
        $activeCount  = JointProject::whereNotIn('status', ['completed', 'canceled'])->count();
        $avgProgress  = $activeCount > 0 
            ? round(JointProject::whereNotIn('status', ['completed', 'canceled'])->avg('progress')) 
            : 0;

        return response()->json([
            'projects' => $projects,
            'stats' => [
                'total'        => $total,
                'active'       => $activeCount,
                'completed'    => $completed,
                'canceled'     => $canceled,
                'avg_progress' => (int) $avgProgress,
            ],
        ]);
    }

    /**
     * POST /api/joint-projects
     */
    public function store(StoreJointProjectRequest $request)
    {
        // Accept both Arabic and English status values
        $statusMap = [
            'قيد الإعداد' => 'planning', 'planning' => 'planning',
            'مستمر'       => 'active',   'active'   => 'active',
            'فكرة'        => 'idea',     'idea'     => 'idea',
            'مكتمل'       => 'completed','completed'=> 'completed',
            'ملغى'        => 'canceled', 'canceled' => 'canceled',
        ];
        if ($request->filled('status')) {
            $request->merge(['status' => $statusMap[$request->status] ?? $request->status]);
        }

        // Grab the numeric ID of the active user (because Auth::id() here returns email)
        $adminId = Auth::user() ? Auth::user()->id : null;

        $project = JointProject::create([
            'name'        => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'status'      => $request->status ?? 'planning',
            'progress'    => 0,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'created_by'  => $adminId,
        ]);

        JointProjectUpdate::create([
            'project_id' => $project->id,
            'body'       => 'تم إنشاء المشروع.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المشروع بنجاح',
            'project' => $this->format($project->load(['category', 'updates'])),
        ]);
    }

    /**
     * PUT /api/joint-projects/{id}
     */
    public function update(UpdateJointProjectRequest $request, $id)
    {
        $project = JointProject::findOrFail($id);

        // Accept both Arabic and English status values
        $statusMap = [
            'قيد الإعداد' => 'planning', 'planning' => 'planning',
            'مستمر'       => 'active',   'active'   => 'active',
            'فكرة'        => 'idea',     'idea'     => 'idea',
            'مكتمل'       => 'completed','completed'=> 'completed',
            'ملغى'        => 'canceled', 'canceled' => 'canceled',
        ];
        if ($request->filled('status')) {
            $request->merge(['status' => $statusMap[$request->status] ?? $request->status]);
        }

        $progress = $request->filled('progress') ? (int)$request->progress : $project->progress;

        // Auto-complete if progress reaches 100
        $status = $request->status ?? $project->status;
        if ($progress >= 100 && !in_array($status, ['completed', 'canceled'])) {
            $status = 'completed';
        }

        $updateData = [
            'name'        => $request->name,
            'description' => $request->description,
            'progress'    => $progress,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'status'      => $status,
        ];
        if ($request->filled('category_id')) {
            $updateData['category_id'] = $request->category_id;
        }

        $project->update($updateData);

        if ($request->filled('update_note')) {
            JointProjectUpdate::create([
                'project_id' => $project->id,
                'body'       => $request->update_note,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المشروع بنجاح',
            'project' => $this->format($project->fresh(['category', 'updates'])),
        ]);
    }

    /**
     * POST /api/joint-projects/{id}/cancel
     */
    public function cancel($id)
    {
        $project = JointProject::findOrFail($id);
        $project->update(['status' => 'canceled']);

        JointProjectUpdate::create([
            'project_id' => $project->id,
            'body'       => 'تم إلغاء المشروع.',
        ]);

        return response()->json(['success' => true, 'message' => 'تم إلغاء المشروع']);
    }

    /**
     * DELETE /api/joint-projects/{id}
     */
    public function destroy($id)
    {
        $project = JointProject::findOrFail($id);
        $project->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المشروع نهائياً']);
    }

    /**
     * GET /api/project-join-requests
     * Returns all project join requests for admin review
     */
    public function joinRequests()
    {
        $requests = \App\Models\OpportunityRequest::with(['user', 'opportunity'])
            ->whereNotNull('project_id')
            ->orWhere(function($q) {
                // fallback: fetch any requests that have notes referencing joint projects
                $q->whereNull('opportunity_id');
            })
            ->latest()
            ->get()
            ->map(function ($r) {
                return [
                    'id'         => $r->id,
                    'status'     => $r->status,
                    'notes'      => $r->notes,
                    'created_at' => $r->created_at,
                    'user'       => $r->user ? [
                        'id'        => $r->user->id,
                        'full_name' => $r->user->full_name ?? $r->user->name,
                    ] : null,
                    'project'    => $r->project ? [
                        'id'    => $r->project->id,
                        'title' => $r->project->name,
                    ] : null,
                ];
            });

        return response()->json(['requests' => $requests]);
    }

    /**
     * POST /api/project-join-requests/{id}/approve
     */
    public function approveJoinRequest($id)
    {
        $req = \App\Models\OpportunityRequest::with('project')->findOrFail($id);
        $req->update(['status' => 'approved']);

        $projectTitle = $req->project?->name ?? 'المشروع';

        if ($req->user_id) {
            Notification::create([
                'user_id'      => $req->user_id,
                'title'        => 'تم قبول طلب الانضمام للمشروع',
                'body'         => "تمت الموافقة على انضمامك للمشروع المشترك: {$projectTitle}",
                'type'         => 'project_join_approved',
                'is_read'      => false,
                'related_id'   => $req->project_id,
                'related_type' => JointProject::class,
            ]);
        }
        if ($req->association_id) {
            Notification::create([
                'association_id' => $req->association_id,
                'title'          => 'تم قبول طلب الانضمام للمشروع',
                'body'           => "تمت الموافقة على انضمامك للمشروع المشترك: {$projectTitle}",
                'type'           => 'project_join_approved',
                'is_read'        => false,
                'related_id'     => $req->project_id,
                'related_type'   => JointProject::class,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'تم قبول الطلب بنجاح']);
    }

    /**
     * POST /api/project-join-requests/{id}/reject
     */
    public function rejectJoinRequest($id)
    {
        $req = \App\Models\OpportunityRequest::with('project')->findOrFail($id);
        $req->update(['status' => 'rejected']);

        $projectTitle = $req->project?->name ?? 'المشروع';

        if ($req->user_id) {
            Notification::create([
                'user_id'      => $req->user_id,
                'title'        => 'تم رفض طلب الانضمام للمشروع',
                'body'         => "تم رفض طلبك للانضمام للمشروع المشترك: {$projectTitle}",
                'type'         => 'project_join_rejected',
                'is_read'      => false,
                'related_id'   => $req->project_id,
                'related_type' => JointProject::class,
            ]);
        }
        if ($req->association_id) {
            Notification::create([
                'association_id' => $req->association_id,
                'title'          => 'تم رفض طلب الانضمام للمشروع',
                'body'           => "تم رفض طلبك للانضمام للمشروع المشترك: {$projectTitle}",
                'type'           => 'project_join_rejected',
                'is_read'        => false,
                'related_id'     => $req->project_id,
                'related_type'   => JointProject::class,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'تم رفض الطلب']);
    }

    /** Format a project for the API response */
    private function format(JointProject $p): array
    {
        return [
            'id'          => $p->id,
            'name'        => $p->name,
            'category_id' => $p->category_id,
            'category'    => $p->category ? [
                'id'    => $p->category->id,
                'name'  => $p->category->name,
                'icon'  => $p->category->icon,
                'color' => $p->category->color,
            ] : null,
            'description' => $p->description,
            'status'      => $p->status,
            'progress'    => (int) $p->progress,
            'start_date'  => $p->start_date?->format('Y-m-d'),
            'end_date'    => $p->end_date?->format('Y-m-d'),
            'created_at'  => $p->created_at->toDateTimeString(),
            'updates'     => $p->updates->map(fn($u) => [
                'id'         => $u->id,
                'body'       => $u->body,
                'created_at' => $u->created_at->format('Y-m-d'),
            ])->values()->all(),
        ];
    }

    /** User submits a joint project request */
    public function apply(Request $request, $id)
    {
        $project = JointProject::findOrFail($id);
        
        $userId = Auth::check() ? Auth::id() : null;
        $assocId = (!Auth::check() && session()->has('association')) ? session('association.id') : null;

        if (!$userId && !$assocId) {
            return response()->json(['success' => false, 'message' => 'يجب تسجيل الدخول لتقديم طلب']);
        }

        // Prevent duplicate requests
        $query = \App\Models\OpportunityRequest::where('project_id', $id);
        if ($userId) $query->where('user_id', $userId);
        if ($assocId) $query->where('association_id', $assocId);
        
        $existing = $query->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قدّمت طلبًا لهذا المشروع مسبقًا.'
            ]);
        }

        $projectRequest = \App\Models\OpportunityRequest::create([
            'project_id'     => $id,
            'opportunity_id' => null,
            'user_id'        => $userId,
            'association_id' => $assocId,
            'status'         => 'pending',
            'notes'          => $request->input('notes'),
        ]);

        // Notify admin(s)
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->get();
        $applicantName = Auth::check() ? Auth::user()->full_name : session('association.name', 'جمعية');

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'      => $admin->id,
                'title'        => 'طلب انضمام لمشروع مشترك',
                'body'         => 'قدّم ' . $applicantName . ' طلبًا للانضمام إلى المشروع: «' . $project->name . '»',
                'type'         => 'project_join',
                'is_read'      => false,
                'related_id'   => $project->id,
                'related_type' => JointProject::class,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلبك بنجاح! سيتم مراجعته من قِبَل الإدارة.',
            'request' => $projectRequest,
        ]);
    }

    /** Return the authenticated user's own project join requests */
    public function myRequests()
    {
        $userId = Auth::check() ? Auth::id() : null;
        $assocId = (!Auth::check() && session()->has('association')) ? session('association.id') : null;

        $query = \App\Models\OpportunityRequest::whereNotNull('project_id')->latest();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($assocId) {
            $query->where('association_id', $assocId);
        } else {
            return response()->json(['requests' => []]);
        }

        $myReqs = $query->get()
            ->map(fn($r) => [
                'id'        => $r->id,
                'projId'    => $r->project_id,
                'status'    => $r->status,
                'notes'     => $r->notes,
            ]);

        return response()->json(['requests' => $myReqs]);
    }
}
