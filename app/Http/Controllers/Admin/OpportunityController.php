<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use App\Http\Requests\Opportunity\RejectOpportunityRequestRequest;
use App\Models\Opportunity;
use App\Models\OpportunityRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunityController extends Controller
{
    /** Returns stats and all opportunities */
    public function index()
    {
        $opportunities = Opportunity::withCount('requests')->latest()->get();

        return response()->json([
            'opportunities' => $opportunities,
            'stats' => [
                'total' => $opportunities->count(),
                'open'  => $opportunities->where('deadline', '>=', now())->count(),
                'pending_requests' => OpportunityRequest::where('status', 'pending')->whereNotNull('opportunity_id')->count(),
                'categories' => $opportunities->pluck('type')->unique()->count(),
            ]
        ]);
    }

    /** Create a new volunteering opportunity */
    public function store(StoreOpportunityRequest $request)
    {
        $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))->first();
        $creatorId = is_numeric(Auth::id()) ? Auth::id() : ($admin ? $admin->id : 1);

        $opportunity = Opportunity::create([
            'created_by'   => $creatorId,
            'title'        => $request->title,
            'type'         => $request->type,
            'description'  => $request->description,
            'requirements' => $request->requirements,
            'deadline'     => $request->deadline,
            'direction'    => $request->direction ?? 'local',
        ]);

        return response()->json(['success' => true, 'message' => 'تم إضافة فرصة التطوع بنجاح', 'opportunity' => $opportunity]);
    }

    /** Update an existing volunteering opportunity */
    public function update(UpdateOpportunityRequest $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);

        $opportunity->update([
            'title'        => $request->title,
            'type'         => $request->type,
            'description'  => $request->description,
            'requirements' => $request->requirements,
            'deadline'     => $request->deadline,
            'direction'    => $request->direction ?? 'local',
        ]);

        return response()->json(['success' => true, 'message' => 'تم تعديل فرصة التطوع بنجاح', 'opportunity' => $opportunity]);
    }

    /** View all requests for volunteering opportunities */
    public function requests(Request $request)
    {
        $status = $request->query('status');

        $query = OpportunityRequest::with(['opportunity', 'association', 'user'])
                    ->whereNotNull('opportunity_id');  // exclude project-join requests

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->get();

        return response()->json(['requests' => $requests]);
    }

    /** Approve an opportunity request */
    public function approveRequest($id)
    {
        $req = OpportunityRequest::with('opportunity')->findOrFail($id);
        $req->update(['status' => 'approved']);

        // Notify the requester — could be a user or an association
        if ($req->user_id) {
            Notification::create([
                'user_id'      => $req->user_id,
                'title'        => 'تم قبول طلب التطوع',
                'body'         => 'لقد تم قبول طلبك للفرصة التطوعية: ' . $req->opportunity->title,
                'type'         => 'opportunity_approved',
                'is_read'      => false,
                'related_id'   => $req->opportunity_id,
                'related_type' => Opportunity::class,
            ]);
        }
        if ($req->association_id) {
            Notification::create([
                'association_id' => $req->association_id,
                'title'          => 'تم قبول طلب التطوع',
                'body'           => 'لقد تم قبول طلبك للفرصة التطوعية: ' . $req->opportunity->title,
                'type'           => 'opportunity_approved',
                'is_read'        => false,
                'related_id'     => $req->opportunity_id,
                'related_type'   => Opportunity::class,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'تم قبول طلب التطوع بنجاح']);
    }

    /** Reject an opportunity request with a reason */
    public function rejectRequest(RejectOpportunityRequestRequest $request, $id)
    {
        $req = OpportunityRequest::with('opportunity')->findOrFail($id);
        $req->update([
            'status' => 'rejected',
            'notes'  => $request->input('notes')
        ]);

        // Notify the requester — could be a user or an association
        if ($req->user_id) {
            Notification::create([
                'user_id'      => $req->user_id,
                'title'        => 'تم رفض طلب التطوع',
                'body'         => "لقد تم رفض طلبك للفرصة التطوعية: {$req->opportunity->title}. السبب: {$req->notes}",
                'type'         => 'opportunity_rejected',
                'is_read'      => false,
                'related_id'   => $req->opportunity_id,
                'related_type' => Opportunity::class,
            ]);
        }
        if ($req->association_id) {
            Notification::create([
                'association_id' => $req->association_id,
                'title'          => 'تم رفض طلب التطوع',
                'body'         => "لقد تم رفض طلبك للفرصة التطوعية: {$req->opportunity->title}. السبب: {$req->notes}",
                'type'           => 'opportunity_rejected',
                'is_read'        => false,
                'related_id'     => $req->opportunity_id,
                'related_type'   => Opportunity::class,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'تم رفض طلب التطوع']);
    }

    /** Delete an opportunity */
    public function destroy($id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $opportunity->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف فرصة التطوع']);
    }

    /** User submits a volunteer opportunity request */
    public function apply(Request $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);
        
        $userId = Auth::check() ? Auth::id() : null;
        $assocId = (!Auth::check() && session()->has('association')) ? session('association.id') : null;

        if (!$userId && !$assocId) {
            return response()->json(['success' => false, 'message' => 'يجب تسجيل الدخول لتقديم طلب']);
        }

        // Prevent duplicate requests
        $query = OpportunityRequest::where('opportunity_id', $id);
        if ($userId) $query->where('user_id', $userId);
        if ($assocId) $query->where('association_id', $assocId);
        
        $existing = $query->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'لقد قدّمت طلبًا لهذه الفرصة مسبقًا.'
            ]);
        }

        $opportunityRequest = OpportunityRequest::create([
            'opportunity_id' => $id,
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
                'title'        => 'طلب تطوع جديد',
                'body'         => 'قدّم ' . $applicantName . ' طلبًا للانضمام إلى فرصة التطوع: «' . $opportunity->title . '»',
                'type'         => 'volunteer_request',
                'is_read'      => false,
                'related_id'   => $opportunity->id,
                'related_type' => Opportunity::class,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلبك بنجاح! سيتم مراجعته من قِبَل الإدارة.',
            'request' => $opportunityRequest,
        ]);
    }

    /** Return the authenticated user's own opportunity requests */
    public function myRequests()
    {
        $userId = Auth::check() ? Auth::id() : null;
        $assocId = (!Auth::check() && session()->has('association')) ? session('association.id') : null;

        $query = OpportunityRequest::with('opportunity')->latest();
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($assocId) {
            $query->where('association_id', $assocId);
        } else {
            return response()->json(['requests' => []]);
        }

        $myReqs = $query->get()
            ->map(fn($r) => [
                'id'     => $r->id,
                'oppId'  => $r->opportunity_id,
                'status' => $r->status,
                'notes'  => $r->notes,
            ]);

        return response()->json(['requests' => $myReqs]);
    }
}
