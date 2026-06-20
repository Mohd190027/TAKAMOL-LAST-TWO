<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssociationRequest\RejectAssociationRequest;
use App\Http\Requests\AssociationRequest\RequestReviewRequest;
use App\Models\Association;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AssociationRequestController extends Controller
{
    /** Return all registration requests with optional status filter */
    public function index(Request $request)
    {
        $query = Association::select(
            'id','association_name','email','license_number',
            'category','manager_name','phone',
            'status','admin_notes','reviewed_at','created_at'
        )->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }

    /** Approve a registration request */
    public function approve(Request $request, $id)
    {
        $assoc = Association::findOrFail($id);
        $assoc->update([
            'status'      => 'approved',
            'admin_notes' => $request->input('notes'),
            'reviewed_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم قبول الطلب']);
    }

    /** Reject a registration request */
    public function reject(RejectAssociationRequest $request, $id)
    {
        $assoc = Association::findOrFail($id);
        $assoc->update([
            'status'      => 'rejected',
            'admin_notes' => $request->input('notes'),
            'reviewed_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم رفض الطلب']);
    }

    /** Request modification — send the association a message with required changes */
    public function requestReview(RequestReviewRequest $request, $id)
    {
        $assoc = Association::findOrFail($id);
        $assoc->update([
            'status'      => 'review',      // custom status: needs revision
            'admin_notes' => $request->input('notes'),
            'reviewed_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب التعديل']);
    }
}
