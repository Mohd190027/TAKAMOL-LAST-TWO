<?php

namespace App\Http\Requests\JointProject;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateJointProjectRequest extends FormRequest
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
            'category_id' => 'nullable|exists:association_categories,id',
            'description' => 'required|string',
            'progress'    => 'nullable|integer|min:0|max:100',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date',
            'status'      => 'nullable|in:planning,active,idea,completed,canceled',
            'update_note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * رسائل الخطأ العربية
     */
    public function messages(): array
    {
        return [
            'name.required'        => 'اسم المشروع مطلوب',
            'name.string'          => 'اسم المشروع يجب أن يكون نصاً',
            'name.max'             => 'اسم المشروع يجب ألا يتجاوز 255 حرفاً',
            'category_id.exists'   => 'التصنيف المحدد غير موجود',
            'description.required' => 'وصف المشروع مطلوب',
            'description.string'   => 'الوصف يجب أن يكون نصاً',
            'progress.integer'     => 'نسبة التقدم يجب أن تكون رقماً صحيحاً',
            'progress.min'         => 'نسبة التقدم يجب ألا تقل عن 0',
            'progress.max'         => 'نسبة التقدم يجب ألا تتجاوز 100',
            'start_date.date'      => 'تاريخ البدء يجب أن يكون تاريخاً صحيحاً',
            'end_date.date'        => 'تاريخ النهاية يجب أن يكون تاريخاً صحيحاً',
            'status.in'            => 'حالة المشروع غير صحيحة',
            'update_note.string'   => 'ملاحظة التحديث يجب أن تكون نصاً',
            'update_note.max'      => 'ملاحظة التحديث يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
