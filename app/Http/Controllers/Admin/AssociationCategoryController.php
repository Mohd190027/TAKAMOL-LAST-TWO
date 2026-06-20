<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssociationCategory\StoreAssociationCategoryRequest;
use App\Http\Requests\AssociationCategory\UpdateAssociationCategoryRequest;
use App\Models\Association;
use App\Models\AssociationCategory;
use Illuminate\Http\Request;

class AssociationCategoryController extends Controller
{
    /**
     * GET /api/association-categories
     * Returns all categories with association counts
     */
    public function index()
    {
        $categories = AssociationCategory::orderBy('name')->get()->map(function ($cat) {
            $totalCount    = Association::where('category', $cat->name)->count();
            $approvedCount = Association::where('category', $cat->name)->where('status', 'approved')->count();
            $pendingCount  = Association::where('category', $cat->name)->where('status', 'pending')->count();
            return [
                'id'          => $cat->id,
                'name'        => $cat->name,
                'icon'        => $cat->icon,
                'color'       => $cat->color,
                'description' => $cat->description,
                'is_active'   => $cat->is_active,
                'total_count'    => $totalCount,
                'approved_count' => $approvedCount,
                'pending_count'  => $pendingCount,
                'fill_percentage'=> $totalCount > 0 ? min(100, round($approvedCount / $totalCount * 100)) : 0,
            ];
        });

        return response()->json(['categories' => $categories]);
    }

    /**
     * POST /api/association-categories
     * Create a new category
     */
    public function store(StoreAssociationCategoryRequest $request)
    {
        $category = AssociationCategory::create([
            'name'        => $request->name,
            'icon'        => $request->icon  ?? '🏢',
            'color'       => $request->color ?? '#2ab8d0',
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'تم إضافة التصنيف بنجاح',
            'category' => $category,
        ]);
    }

    /**
     * PUT /api/association-categories/{id}
     * Update a category
     */
    public function update(UpdateAssociationCategoryRequest $request, $id)
    {
        $category = AssociationCategory::findOrFail($id);

        // If name changes, update all associations using the old name
        $oldName = $category->name;
        if ($oldName !== $request->name) {
            Association::where('category', $oldName)->update(['category' => $request->name]);
        }

        $category->update([
            'name'        => $request->name,
            'icon'        => $request->icon        ?? $category->icon,
            'color'       => $request->color       ?? $category->color,
            'description' => $request->description ?? $category->description,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'تم تحديث التصنيف بنجاح',
            'category' => $category,
        ]);
    }

    /**
     * DELETE /api/association-categories/{id}
     * Delete a category (only if no associations are using it)
     */
    public function destroy($id)
    {
        $category = AssociationCategory::findOrFail($id);
        $count = Association::where('category', $category->name)->count();

        if ($count > 0) {
            return response()->json([
                'success' => false,
                'message' => "لا يمكن حذف هذا التصنيف لأنه مرتبط بـ {$count} جمعية",
            ], 422);
        }

        $category->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف التصنيف بنجاح']);
    }

    /**
     * GET /api/associations
     * Returns all registered associations with full details
     */
    public function associations(Request $request)
    {
        $query = Association::select(
            'id', 'association_name', 'email', 'license_number',
            'category', 'manager_name', 'phone',
            'status', 'admin_notes', 'reviewed_at', 'created_at'
        );

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('association_name', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $associations = $query->orderBy('created_at', 'desc')->get();

        $stats = [
            'total'    => Association::count(),
            'approved' => Association::where('status', 'approved')->count(),
            'pending'  => Association::where('status', 'pending')->count(),
            'rejected' => Association::where('status', 'rejected')->count(),
            'review'   => Association::where('status', 'review')->count(),
        ];

        return response()->json([
            'associations' => $associations,
            'stats'        => $stats,
        ]);
    }
}
