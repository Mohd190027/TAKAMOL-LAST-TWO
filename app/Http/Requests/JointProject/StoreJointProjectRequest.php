<?php

namespace App\Http\Requests\JointProject;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreJointProjectRequest extends FormRequest
{
    /**
     * تحقق من صلاحية المستخدم لتنفيذ هذا الطلب
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role?->name === 'admin';
    }

    /**
     * قواعد التحقق
     */
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'category_id' => 'required|exists:association_categories,id',
            'description' => 'required|string',
            'status'      => 'nullable|in:planning,active,idea,completed,canceled',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'name.required'           => 'اسم المشروع مطلوب',
            'name.string'             => 'اسم المشروع يجب أن يكون نصاً',
            'name.max'                => 'اسم المشروع يجب ألا يتجاوز 255 حرفاً',
            'category_id.required'    => 'التصنيف مطلوب',
            'category_id.exists'      => 'التصنيف المحدد غير موجود',
            'description.required'    => 'وصف المشروع مطلوب',
            'description.string'      => 'الوصف يجب أن يكون نصاً',
            'status.in'               => 'حالة المشروع غير صحيحة',
            'start_date.date'         => 'تاريخ البدء يجب أن يكون تاريخاً صحيحاً',
            'end_date.date'           => 'تاريخ النهاية يجب أن يكون تاريخاً صحيحاً',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البدء',
        ];
    }
}
